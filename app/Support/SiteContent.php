<?php

namespace App\Support;

/**
 * Static marketing copy for the storefront. Policy text lives in the database (Admin-editable).
 */
final class SiteContent
{
    /**
     * @return list<string>
     */
    public static function produce(): array
    {
        return ['Tomatoes', 'Tatashe', 'Ata rodo', 'Ugu', 'Scent leaf', 'Onions', 'Irish potatoes', 'Carrots', 'Cabbage', 'Lettuce', 'Cucumber', 'Green pepper'];
    }

    /**
     * @return list<array{title: string, body: string}>
     */
    public static function steps(): array
    {
        return [
            ['title' => 'Reserve a slot', 'body' => 'Each Run has a fixed number of slots. Pick your boxes and add-ons, then pay in full to lock in your slot.'],
            ['title' => 'We pre-book farmers', 'body' => 'Once orders close, our Jos team books exactly what was ordered from trusted Plateau farmers.'],
            ['title' => 'Bought, packed, moved', 'body' => 'On market day we buy, sort and pack your box, then send it to Abuja the same day with an escort.'],
            ['title' => 'Collect or receive', 'body' => 'Pick up at our Abuja hub with your code from the evening of arrival until noon the next day — or have a rider bring it.'],
        ];
    }

    /**
     * @return list<array{q: string, a: string}>
     */
    public static function faqs(): array
    {
        return [
            ['q' => 'Why are there limited slots?', 'a' => 'Every box travels in one pre-booked car, so each Run has a fixed capacity. One box uses one slot; add-ons don\'t use slots. When slots run out, ordering closes and you can join the waitlist.'],
            ['q' => 'When do I pay?', 'a' => 'You pay in full at checkout by card, bank transfer or USSD. Your slot is held for about 10 minutes while you pay, and your order is confirmed only once the payment is verified.'],
            ['q' => 'What if an item isn\'t available on market day?', 'a' => 'We substitute it with an item of equal or greater value, like-for-like where possible, and never more than two items per box. If there\'s no fair substitute, its value is added to your JosVeggies credit. We tell you before pickup.'],
            ['q' => 'How long do I have to collect?', 'a' => 'Your pickup window starts when the Run arrives at our Abuja hub (we\'ll message your code) and ends at 12 noon the next day. Orders not collected in time are forfeited without refund — please make sure you can collect or choose rider dispatch.'],
            ['q' => 'Can I get delivery instead?', 'a' => 'Yes. Choose rider dispatch at checkout and pay a fixed fee for your zone. If a delivery attempt fails, your order returns to the hub and the same noon deadline applies.'],
            ['q' => 'How does JosVeggies credit work?', 'a' => 'Credit comes from substitutions or unavailable add-ons. It is applied automatically to your next order if you choose, and every entry is listed in your account. Credit can\'t be topped up or withdrawn.'],
            ['q' => 'What happens if a Run is cancelled?', 'a' => 'In the rare case we cancel a Run, your payment is refunded in full through the payment gateway and any credit you used is returned.'],
        ];
    }
}
