<?php

namespace App\Services;

use App\Enums\CreditReason;
use App\Enums\OrderState;
use App\Enums\PaymentStatus;
use App\Enums\RunState;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Run;
use App\Models\User;
use App\Notifications\OrderUpdate;
use App\Services\Payments\GatewayTransaction;
use App\Services\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * BR-08: an order is confirmed only after the gateway itself confirms payment.
 * Every handler here is idempotent: replays of the same webhook change nothing.
 */
class PaymentService
{
    /**
     * Expired orders are cancelled (and their credit returned) after this many minutes,
     * leaving time for a late gateway confirmation to arrive first.
     */
    public const EXPIRED_GRACE_MINUTES = 60;

    public function __construct(
        private PaymentGateway $gateway,
        private OrderStateMachine $orders,
        private CreditService $credit,
    ) {}

    /**
     * Create a payment attempt and return the hosted checkout URL.
     */
    public function start(Order $order): string
    {
        if ($order->state !== OrderState::PendingPayment || $order->hold_expires_at?->isPast()) {
            throw new BusinessRuleException('This order can no longer be paid. Please start a new order.');
        }

        $payment = new Payment;
        $payment->order_id = $order->id;
        $payment->provider = $this->gateway->name();
        $payment->reference = 'JV'.now()->format('ymd').Order::randomCode(10);
        $payment->amount_kobo = $order->total_kobo;
        $payment->currency = (string) config('josveggies.payments.currency');
        $payment->status = PaymentStatus::Pending;
        $payment->save();
        $payment->setRelation('order', $order);

        return $this->gateway->initialize($payment, $order->user->email, route('checkout.return', ['reference' => $payment->reference]));
    }

    /**
     * Verify a reference with the gateway and apply the result. Used by the webhook and by the
     * return page as a fallback when the webhook is delayed (still a server-to-server check).
     */
    public function verifyAndApply(string $reference): ?Payment
    {
        $payment = Payment::query()->where('reference', $reference)->first();

        if ($payment === null) {
            Log::warning('Payment verification for unknown reference', ['reference' => $reference]);

            return null;
        }

        if ($payment->status !== PaymentStatus::Pending) {
            return $payment;
        }

        $transaction = $this->gateway->verify($reference);

        return $transaction->successful
            ? $this->applySuccessfulCharge($transaction)
            : $payment;
    }

    public function applySuccessfulCharge(GatewayTransaction $transaction): ?Payment
    {
        $refundAfterCommit = null;

        $payment = DB::transaction(function () use ($transaction, &$refundAfterCommit): ?Payment {
            $payment = Payment::query()->where('reference', $transaction->reference)->lockForUpdate()->first();

            if ($payment === null || $payment->status !== PaymentStatus::Pending) {
                return $payment;
            }

            if ($transaction->amountKobo !== $payment->amount_kobo || $transaction->currency !== $payment->currency) {
                $payment->status = PaymentStatus::Failed;
                $payment->gateway_response = $transaction->raw + ['mismatch' => true];
                $payment->save();
                Log::critical('Payment amount/currency mismatch', ['reference' => $payment->reference]);
                activity('payments')->performedOn($payment)->log('payment.mismatch');

                return $payment;
            }

            $payment->status = PaymentStatus::Succeeded;
            $payment->channel = $transaction->channel;
            $payment->paid_at = now();
            $payment->gateway_response = $transaction->raw;
            $payment->save();
            activity('payments')->performedOn($payment)->log('payment.succeeded');

            /** @var Run $run */
            $run = Run::query()->lockForUpdate()->findOrFail($payment->order->run_id);
            /** @var Order $order */
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);

            if ($order->state === OrderState::PendingPayment) {
                $run->slots_held = max(0, $run->slots_held - $order->slots);
                $this->confirm($order, $run);
            } elseif ($order->state === OrderState::Expired && $run->state === RunState::Open && $run->slotsRemaining() >= $order->slots) {
                // Late payment, but the slots are still free: honour it.
                $this->confirm($order, $run);
            } elseif (in_array($order->state, [OrderState::Expired, OrderState::Cancelled], true)) {
                // Late payment and the slots are gone: cancel and refund automatically.
                if ($order->state === OrderState::Expired) {
                    $this->releaseCredit($order);
                    $this->orders->transition($order, OrderState::Cancelled, null, 'Payment arrived after the slot was released');
                }
                $refundAfterCommit = $payment;
            }

            $run->save();

            return $payment;
        });

        if ($refundAfterCommit !== null) {
            $this->refund($refundAfterCommit, 'Late payment — no slot available');
            $refundAfterCommit->order->user->notify(new OrderUpdate($refundAfterCommit->order->fresh(), 'refunded_late'));
        }

        return $payment;
    }

    public function markFailed(string $reference): void
    {
        Payment::query()
            ->where('reference', $reference)
            ->where('status', PaymentStatus::Pending)
            ->update(['status' => PaymentStatus::Failed]);
    }

    /**
     * Confirm a PendingPayment order paid in full by credit (no gateway involved).
     */
    public function confirmWithoutGateway(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            /** @var Run $run */
            $run = Run::query()->lockForUpdate()->findOrFail($order->run_id);
            $run->slots_held = max(0, $run->slots_held - $order->slots);
            $this->confirm($order, $run);
            $run->save();
        });
    }

    /**
     * BR-06: release slots held by orders whose payment window has passed.
     */
    public function expireStaleHolds(): int
    {
        $count = 0;

        Order::query()
            ->where('state', OrderState::PendingPayment)
            ->where('hold_expires_at', '<=', now())
            ->pluck('id')
            ->each(function (int $orderId) use (&$count): void {
                DB::transaction(function () use ($orderId, &$count): void {
                    /** @var Order $order */
                    $order = Order::query()->lockForUpdate()->findOrFail($orderId);

                    if ($order->state !== OrderState::PendingPayment) {
                        return;
                    }

                    /** @var Run $run */
                    $run = Run::query()->lockForUpdate()->findOrFail($order->run_id);
                    $run->slots_held = max(0, $run->slots_held - $order->slots);
                    $run->save();
                    $this->orders->transition($order, OrderState::Expired);
                    $count++;
                });
            });

        Order::query()
            ->where('state', OrderState::Expired)
            ->where('hold_expires_at', '<=', now()->subMinutes(self::EXPIRED_GRACE_MINUTES))
            ->get()
            ->each(fn (Order $order) => $this->cancelOrder($order, 'Payment not completed'));

        return $count;
    }

    /**
     * Cancel an order, releasing its slots and credit, and refund any successful payment.
     */
    public function cancelOrder(Order $order, string $reason, ?User $actor = null, bool $refund = true): void
    {
        $toRefund = DB::transaction(function () use ($order, $reason, $actor): array {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->state === OrderState::Cancelled) {
                return [];
            }

            /** @var Run $run */
            $run = Run::query()->lockForUpdate()->findOrFail($locked->run_id);

            if ($locked->state === OrderState::PendingPayment) {
                $run->slots_held = max(0, $run->slots_held - $locked->slots);
            } elseif (in_array($locked->state, OrderState::paid(), true)) {
                $run->slots_confirmed = max(0, $run->slots_confirmed - $locked->slots);
            }
            $run->save();

            $this->releaseCredit($locked);
            $this->orders->transition($locked, OrderState::Cancelled, $actor, $reason);
            $order->setRawAttributes($locked->getAttributes(), true);

            return $locked->payments()->where('status', PaymentStatus::Succeeded)->get()->all();
        });

        if ($refund) {
            foreach ($toRefund as $payment) {
                $this->refund($payment, $reason);
            }
        }
    }

    public function refund(Payment $payment, string $reason): void
    {
        if ($payment->status !== PaymentStatus::Succeeded) {
            return;
        }

        try {
            $reference = $this->gateway->refund($payment, $payment->amount_kobo);
            $payment->status = PaymentStatus::Refunded;
            $payment->refunded_kobo = $payment->amount_kobo;
            $payment->refund_reference = $reference;
            $payment->refunded_at = now();
            $payment->save();
            activity('payments')->performedOn($payment)->withProperties(['reason' => $reason])->log('payment.refunded');
        } catch (Throwable $e) {
            report($e);
            activity('payments')->performedOn($payment)->withProperties(['reason' => $reason, 'error' => $e->getMessage()])->log('payment.refund_failed');
        }
    }

    private function confirm(Order $order, Run $run): void
    {
        $order->assignPickupCode();
        $this->orders->transition($order, OrderState::Confirmed);
        $run->slots_confirmed += $order->slots;

        $order->user->notify((new OrderUpdate($order, 'confirmed'))->afterCommit());

        // BR-04: close the window the moment every slot is confirmed.
        if ($run->state === RunState::Open && $run->slots_confirmed >= $run->slot_capacity) {
            $run->save();
            app(RunService::class)->transition($run, RunState::Closed);
        }
    }

    private function releaseCredit(Order $order): void
    {
        if ($order->credit_applied_kobo > 0) {
            $this->credit->grant($order->user, $order->credit_applied_kobo, CreditReason::OrderReleased, $order, 'Order '.$order->number);
        }
    }
}
