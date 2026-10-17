<?php

namespace App\Services;

use App\Enums\OrderState;
use App\Enums\Role;
use App\Enums\RunState;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\InvalidTransitionException;
use App\Models\Order;
use App\Models\Run;
use App\Models\Setting;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Notifications\OrderUpdate;
use App\Notifications\RunClosedStaffAlert;
use App\Notifications\RunOpened;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * The Run state machine. Every Run state change goes through transition(), which checks the
 * allowed transitions, the actor's role, cascades the change to orders and triggers notifications.
 */
class RunService
{
    public function __construct(
        private OrderStateMachine $orders,
    ) {}

    /**
     * @param  User|null  $actor  null when a scheduled job acts as the system
     */
    public function transition(Run $run, RunState $to, ?User $actor = null): Run
    {
        if ($actor !== null && ! $actor->hasRole(...$to->rolesAllowedToEnter())) {
            throw new AuthorizationException('Your role cannot move a Run to '.$to->label().'.');
        }

        return DB::transaction(function () use ($run, $to, $actor): Run {
            /** @var Run $locked */
            $locked = Run::query()->lockForUpdate()->findOrFail($run->id);
            $from = $locked->state;

            if (! $from->canTransitionTo($to)) {
                throw InvalidTransitionException::between('Run '.$locked->code, $from, $to);
            }

            $this->guard($locked, $to);
            $locked->state = $to;
            $this->applySideEffects($locked, $to, $actor);
            $locked->save();

            activity('runs')
                ->performedOn($locked)
                ->causedBy($actor)
                ->event('state_changed')
                ->withProperties(['from' => $from->value, 'to' => $to->value, 'system' => $actor === null])
                ->log("Run {$locked->code}: {$from->value} → {$to->value}");

            $run->setRawAttributes($locked->getAttributes(), true);

            return $run;
        });
    }

    /**
     * Jos Ops confirms that everything has been bought from the pre-booked farmers.
     */
    public function markPurchased(Run $run, User $actor): Run
    {
        if (! $actor->hasRole(Role::Admin, Role::JosOps)) {
            throw new AuthorizationException('Only Jos Ops can mark a Run as purchased.');
        }

        return DB::transaction(function () use ($run, $actor): Run {
            /** @var Run $locked */
            $locked = Run::query()->lockForUpdate()->findOrFail($run->id);

            if ($locked->state !== RunState::Procuring || $locked->purchased_at !== null) {
                throw new BusinessRuleException('Purchases can only be recorded once, while the Run is procuring.');
            }

            $locked->purchased_at = now();
            $locked->save();
            $this->cascade($locked, [OrderState::Confirmed], OrderState::Purchased, $actor, 'purchased');

            activity('runs')->performedOn($locked)->causedBy($actor)->log("Run {$locked->code}: purchased");
            $run->setRawAttributes($locked->getAttributes(), true);

            return $run;
        });
    }

    public function pickupDeadlineFor(CarbonInterface $arrivedAt): CarbonImmutable
    {
        return CarbonImmutable::instance($arrivedAt)->addDay()->setTimeFromTimeString((string) Setting::get('pickup_deadline_time'));
    }

    /**
     * Pre-conditions for entering a state.
     */
    private function guard(Run $run, RunState $to): void
    {
        if ($to === RunState::Scheduled) {
            $violations = RunScheduleRules::violations($run->opens_at, $run->closes_at, $run->market_day, $run->delivery_starts_at);

            if ($violations !== []) {
                throw new BusinessRuleException(implode(' ', $violations));
            }

            if ($run->boxTemplates()->doesntExist()) {
                throw new BusinessRuleException('Choose at least one box for this Run before publishing it.');
            }
        }

        if ($to === RunState::Packed && $run->purchased_at === null) {
            throw new BusinessRuleException('Mark the Run as purchased before marking it packed.');
        }
    }

    private function applySideEffects(Run $run, RunState $to, ?User $actor): void
    {
        switch ($to) {
            case RunState::Open:
                if ($run->waitlist_notified_at === null) {
                    $run->waitlist_notified_at = now();
                    $this->notifyWaitlist($run);
                }
                break;

            case RunState::Closed:
                $run->closed_at = now();
                $staff = User::query()->whereIn('role', [Role::Admin, Role::JosOps])->where('is_active', true)->get();
                Notification::send($staff, (new RunClosedStaffAlert($run))->afterCommit());
                break;

            case RunState::Packed:
                $run->packed_at = now();
                $this->cascade($run, [OrderState::Purchased], OrderState::Packed, $actor, 'packed');
                break;

            case RunState::InTransit:
                $run->departed_at = now();
                $this->cascade($run, [OrderState::Packed], OrderState::InTransit, $actor, 'in_transit');
                break;

            case RunState::Arrived:
                $run->arrived_at = now();
                $run->pickup_ends_at = $this->pickupDeadlineFor(now());
                $this->cascade($run, [OrderState::InTransit], OrderState::ReadyForPickup, $actor, 'arrived');
                break;

            case RunState::PickupClosed:
                // BR-16 / BR-17: everything not collected or delivered by the deadline is forfeited.
                $this->cascade($run, [OrderState::ReadyForPickup, OrderState::OutForDelivery], OrderState::Forfeited, $actor, 'forfeited');
                break;

            case RunState::Reconciled:
                $run->reconciled_at = now();
                break;

            case RunState::Cancelled:
                $run->cancelled_at = now();
                $this->cancelOrders($run, $actor);
                break;

            default:
                break;
        }
    }

    /**
     * Move every order of the Run in one of the $from states to $to, then notify the customer.
     *
     * @param  list<OrderState>  $from
     */
    private function cascade(Run $run, array $from, OrderState $to, ?User $actor, string $event): void
    {
        $run->orders()
            ->whereIn('state', $from)
            ->with('user')
            ->lockForUpdate()
            ->get()
            ->each(function (Order $order) use ($to, $actor, $event): void {
                $this->orders->transition($order, $to, $actor);
                $order->user->notify((new OrderUpdate($order, $event))->afterCommit());
            });
    }

    /**
     * Cancelling a Run refunds paid orders through the gateway and returns any credit used.
     */
    private function cancelOrders(Run $run, ?User $actor): void
    {
        $payments = app(PaymentService::class);

        $run->orders()
            ->whereNotIn('state', [OrderState::Cancelled, OrderState::Collected, OrderState::Delivered, OrderState::Forfeited])
            ->with('user')
            ->lockForUpdate()
            ->get()
            ->each(function (Order $order) use ($payments, $actor): void {
                $wasPaid = in_array($order->state, OrderState::paid(), true);
                $payments->cancelOrder($order, 'Run cancelled', $actor, refund: true);

                if ($wasPaid) {
                    $order->user->notify((new OrderUpdate($order, 'cancelled'))->afterCommit());
                }
            });

        $run->slots_held = 0;
    }

    private function notifyWaitlist(Run $run): void
    {
        WaitlistEntry::query()
            ->whereNull('notified_at')
            ->with('user')
            ->get()
            ->unique('user_id')
            ->each(function (WaitlistEntry $entry) use ($run): void {
                $entry->user->notify((new RunOpened($run))->afterCommit());
                WaitlistEntry::query()->where('user_id', $entry->user_id)->whereNull('notified_at')->update(['notified_at' => now()]);
            });
    }
}
