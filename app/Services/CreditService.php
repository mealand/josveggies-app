<?php

namespace App\Services;

use App\Enums\CreditReason;
use App\Models\CreditLedgerEntry;
use App\Models\Order;
use App\Models\User;
use InvalidArgumentException;

/**
 * Every credit and every use of credit is a separate ledger entry (Webapp §11, BR-20).
 * Credit cannot be topped up or withdrawn; it is only earned from adjustments and spent at checkout.
 */
class CreditService
{
    public function grant(User $user, int $amountKobo, CreditReason $reason, ?Order $order = null, ?string $description = null, ?User $actor = null): CreditLedgerEntry
    {
        if ($amountKobo <= 0) {
            throw new InvalidArgumentException('Credit amount must be positive.');
        }

        return $this->record($user, $amountKobo, $reason, $order, $description, $actor);
    }

    /**
     * Spend credit on an order. Call inside a transaction holding a lock on the user row.
     */
    public function spend(User $user, int $amountKobo, Order $order): CreditLedgerEntry
    {
        if ($amountKobo <= 0) {
            throw new InvalidArgumentException('Spend amount must be positive.');
        }

        if ($amountKobo > $user->creditBalanceKobo()) {
            throw new InvalidArgumentException('Insufficient credit.');
        }

        return $this->record($user, -$amountKobo, CreditReason::AppliedToOrder, $order, 'Order '.$order->number, $user);
    }

    private function record(User $user, int $amountKobo, CreditReason $reason, ?Order $order, ?string $description, ?User $actor): CreditLedgerEntry
    {
        $entry = new CreditLedgerEntry;
        $entry->user_id = $user->id;
        $entry->order_id = $order?->id;
        $entry->amount_kobo = $amountKobo;
        $entry->reason = $reason;
        $entry->description = $description;
        $entry->created_by = $actor?->id;
        $entry->save();

        activity('credit')
            ->performedOn($entry)
            ->causedBy($actor)
            ->withProperties(['user_id' => $user->id, 'amount_kobo' => $amountKobo, 'reason' => $reason->value, 'order_id' => $order?->id])
            ->log($amountKobo > 0 ? 'credit.granted' : 'credit.spent');

        return $entry;
    }
}
