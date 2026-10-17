<?php

namespace Database\Seeders;

use App\Models\Policy;
use Illuminate\Database\Seeder;

/**
 * Draft policy wording (concept doc open decision #7). To be reviewed by the founder and a lawyer
 * before launch; Admin can publish new versions from Console → Settings. Safe to run in production:
 * it only creates policies that don't exist yet.
 */
class PolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            'forfeiture' => <<<'MD'
## Pickup window

Your pickup window **starts when your Run is marked as arrived** at our Abuja hub (you'll receive an SMS and email with your pickup code) and **ends at 12 noon the following day**.

## Forfeiture

Orders that are not collected — or delivered by rider — before the end of the pickup window are **forfeited without refund**. Vegetables are perishable and bought specifically for your order, so we cannot hold them.

## Rider dispatch

If a rider cannot deliver your order, it returns to the hub and the **same noon deadline** applies. Please keep your phone reachable on delivery day.

## Reminders

We send an arrival message, a morning reminder (around 9am) and a final warning (around 11am).
MD,
            'substitution' => <<<'MD'
## Why substitutions happen

We buy fresh on market day from pre-booked farmers. Occasionally an item is unavailable or its price spikes.

## Our rules

- A substitute is always of **equal or greater value** than the item it replaces.
- We substitute **like-for-like** where possible (e.g. one leafy green for another).
- **At most two items per box** are ever substituted.
- If no fair substitute exists, the item's value is added to your **JosVeggies credit**.
- Add-ons are never substituted; if unavailable, their full price is added to your credit.
- We tell you about every substitution **before pickup**.
MD,
            'refunds' => <<<'MD'
## Payments

Orders are paid in full at checkout. An order is confirmed only after our payment provider verifies your payment.

## Refunds

- If we cancel a Run, you receive a **full refund** to your original payment method, and any credit you used is returned.
- If your payment arrives after your slot was released and the Run is full, it is **refunded automatically**.
- Forfeited orders are not refunded (see the pickup & forfeiture policy).

## JosVeggies credit

Credit comes from substitutions and unavailable add-ons. It can be applied to future orders only and **cannot be topped up, transferred or withdrawn**. Every credit entry is listed in your account.
MD,
            'privacy' => <<<'MD'
JosVeggies handles your personal data in line with the **Nigeria Data Protection Act 2023**.

## What we collect

- Your name, email address and phone number — to manage your account and send order updates.
- A delivery address — only if you choose rider dispatch.
- Order and payment records — card details are handled by our payment provider and never stored by us.

## How we use it

To fulfil your orders, send transactional SMS and email messages, provide support and keep financial records.

## Your rights

You can ask for a copy of your data, its correction or its deletion by emailing us. We keep financial records for as long as the law requires.

## Security

Data is encrypted in transit, sensitive fields are encrypted at rest and access is restricted by role.
MD,
            'terms' => <<<'MD'
These terms govern your use of JosVeggies.

1. Orders are pre-orders for a specific Run and are subject to slot availability.
2. Prices are in naira and include VAT where applicable.
3. By paying, you accept the pickup & forfeiture, substitution and refunds policies.
4. Box contents are curated and may be substituted under the substitution policy.
5. JosVeggies may cancel a Run in exceptional circumstances, in which case you will be refunded in full.
MD,
        ];

        foreach ($policies as $slug => $body) {
            if (Policy::current($slug) === null) {
                Policy::query()->create([
                    'slug' => $slug,
                    'title' => Policy::SLUGS[$slug],
                    'body' => "_Draft — to be reviewed before launch._\n\n".$body,
                    'version' => 1,
                ]);
            }
        }
    }
}
