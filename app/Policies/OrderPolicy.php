<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Customers see only their own orders; staff can view any order.
     */
    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id || $user->isStaff();
    }
}
