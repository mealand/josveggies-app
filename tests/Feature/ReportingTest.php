<?php

use App\Models\User;
use App\Services\ReportService;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

test('BR-21: costs are recorded against a Run and drive its margin', function () {
    ['run' => $run, 'box' => $box] = openRun();
    paidOrder(User::factory()->create(), $run, $box, boxes: 2);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('console.reports.costs.store', $run), ['category' => 'transport', 'amount' => '20000', 'description' => 'Cargo fee'])
        ->assertRedirect();

    $pnl = app(ReportService::class)->profitAndLoss($run);

    expect($pnl['revenue_kobo'])->toBe(3000000)
        ->and($pnl['cost_total_kobo'])->toBe(2000000)
        ->and($pnl['margin_kobo'])->toBe(1000000)
        ->and($pnl['boxes_sold'])->toBe(2);
});

test('admins can open the reports dashboard', function () {
    openRun();

    $this->actingAs(User::factory()->admin()->create())->get(route('console.reports.index'))->assertOk()->assertSee('Run performance');
});
