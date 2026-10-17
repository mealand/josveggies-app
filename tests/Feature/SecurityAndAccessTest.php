<?php

use App\Enums\CreditReason;
use App\Enums\Role;
use App\Models\CreditLedgerEntry;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

test('BR-19: customers never see the market day', function () {
    ['run' => $run] = openRun(runAttributes: ['market_day' => now()->addDays(5)->startOfDay(), 'delivery_starts_at' => now()->addDays(6)->setTime(9, 0)]);
    $marketDay = $run->market_day;

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toContain($marketDay->format('l j M'))
        ->not->toContain($marketDay->format('D j M'))
        ->not->toContain($marketDay->toDateString())
        ->toContain($run->delivery_starts_at->format('l j M'));
});

test('BR-19: Abuja Ops do not see the market day but Jos Ops do', function () {
    ['run' => $run] = openRun(runAttributes: ['market_day' => now()->addDays(5)->startOfDay(), 'delivery_starts_at' => now()->addDays(6)->setTime(9, 0)]);
    $label = $run->market_day->format('l j M Y');

    $this->actingAs(User::factory()->abujaOps()->create())->get(route('console.runs.show', $run))
        ->assertOk()->assertDontSee($label)->assertDontSee('Market day');

    $this->actingAs(User::factory()->josOps()->create())->get(route('console.runs.show', $run))
        ->assertOk()->assertSee($label);
});

test('the market day is never serialised from the model', function () {
    ['run' => $run] = openRun();

    expect($run->toArray())->not->toHaveKey('market_day')->not->toHaveKey('internal_notes');
});

test('customers cannot open the Operations Console', function () {
    $this->actingAs(User::factory()->create())->get('/console')->assertForbidden();
});

test('staff must set up two-factor authentication before using the console', function () {
    $staff = User::factory()->role(Role::JosOps)->create();

    $this->actingAs($staff)->get('/console')->assertRedirect(route('console.security'));
    $this->actingAs($staff)->get(route('console.security'))->assertOk();
});

test('roles are enforced on console areas', function () {
    ['run' => $run] = openRun();

    $this->actingAs(User::factory()->abujaOps()->create())->get(route('console.jos.procurement', $run))->assertForbidden();
    $this->actingAs(User::factory()->josOps()->create())->get(route('console.abuja.pickup'))->assertForbidden();
    $this->actingAs(User::factory()->josOps()->create())->get(route('console.reports.index'))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get(route('console.reports.index'))->assertOk();
});

test('customers can only see their own orders', function () {
    ['run' => $run, 'box' => $box] = openRun();
    $order = paidOrder(User::factory()->create(), $run, $box);

    $this->actingAs(User::factory()->create())->get(route('account.orders.show', $order))->assertForbidden();
    $this->actingAs($order->user)->get(route('account.orders.show', $order))->assertOk()->assertSee($order->pickup_code);
});

test('inactive accounts cannot sign in and staff cannot self-register as staff', function () {
    $user = User::factory()->create(['password' => bcrypt('a-strong-pass-123')]);
    $user->forceFill(['is_active' => false])->save();

    $this->post('/login', ['email' => $user->email, 'password' => 'a-strong-pass-123']);
    $this->assertGuest();

    $this->post('/register', [
        'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'phone' => '08031234567',
        'password' => 'password12345', 'password_confirmation' => 'password12345', 'privacy' => '1', 'role' => 'admin',
    ]);
    expect(User::query()->where('email', 'sneaky@example.com')->first()?->isStaff())->toBeFalse();
});

test('BR-20: the credit ledger is append-only', function () {
    $user = User::factory()->create();
    $entry = app(CreditService::class)->grant($user, 1000, CreditReason::ManualAdjustment);

    expect(function () use ($entry) {
        $entry->amount_kobo = 99999;
        $entry->save();
    })->toThrow(LogicException::class)
        ->and(fn () => $entry->delete())->toThrow(LogicException::class)
        ->and(CreditLedgerEntry::query()->sum('amount_kobo'))->toEqual(1000);
});

test('security headers and CSP are sent', function () {
    $this->get('/')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Security-Policy');
});
