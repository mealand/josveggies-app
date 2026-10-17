<?php

use App\Models\BoxTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

test('storefront and account pages render', function () {
    ['run' => $run, 'box' => $box] = openRun();
    $customer = User::factory()->create();
    $order = paidOrder($customer, $run, $box);

    foreach (['/', '/how-it-works', '/faq', '/contact', '/policies/substitution', '/login', '/register'] as $url) {
        $this->get($url)->assertOk();
    }

    $this->actingAs($customer);
    foreach ([route('checkout'), route('account.dashboard'), route('account.orders.show', $order), route('account.credit'), route('account.profile')] as $url) {
        $this->get($url)->assertOk();
    }
    $this->get(route('account.orders.qr', $order))->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
});

test('every console page renders for an admin', function () {
    ['run' => $run, 'box' => $box] = openRun();
    paidOrder(User::factory()->create(), $run, $box);
    $this->actingAs(User::factory()->admin()->create())->withSession(['auth.password_confirmed_at' => time()]);

    $urls = [
        route('console.dashboard'), route('console.security'), route('console.runs.index'), route('console.runs.create'),
        route('console.runs.show', $run), route('console.runs.edit', $run),
        route('console.catalogue.boxes.index'), route('console.catalogue.boxes.create'), route('console.catalogue.boxes.edit', BoxTemplate::first()),
        route('console.catalogue.items.index'), route('console.catalogue.items.create'),
        route('console.catalogue.pack-units.index'), route('console.catalogue.pack-units.create'),
        route('console.catalogue.add-ons.index'), route('console.catalogue.add-ons.create'),
        route('console.zones.index'), route('console.riders.index'), route('console.staff.index'), route('console.settings.edit'),
        route('console.reports.index'), route('console.reports.run', $run), route('console.audit'),
        route('console.jos.procurement', $run), route('console.jos.packing', $run), route('console.jos.substitutions', $run),
        route('console.abuja.pickup'), route('console.abuja.dispatch'), route('console.abuja.forfeitures', $run),
    ];

    foreach ($urls as $url) {
        $this->get($url)->assertOk();
    }

    foreach ([route('console.jos.procurement.pdf', $run), route('console.jos.packing.pdf', $run), route('console.jos.labels.pdf', $run)] as $url) {
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
});
