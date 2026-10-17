<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Console;
use App\Http\Controllers\FakePayController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Customer Storefront
|--------------------------------------------------------------------------
*/

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/how-it-works', [StorefrontController::class, 'how'])->name('pages.how');
Route::get('/faq', [StorefrontController::class, 'faq'])->name('pages.faq');
Route::get('/contact', [StorefrontController::class, 'contact'])->name('pages.contact');
Route::get('/policies/{slug}', [StorefrontController::class, 'policy'])->name('pages.policy')->where('slug', '[a-z]+');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::get('/checkout/return', [CheckoutController::class, 'return'])->name('checkout.return');
});

Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders/{order}', [AccountController::class, 'showOrder'])->name('orders.show');
    Route::get('/orders/{order}/qr.svg', [AccountController::class, 'qr'])->name('orders.qr');
    Route::post('/orders/{order}/pay', [AccountController::class, 'pay'])->middleware(['verified', 'throttle:checkout'])->name('orders.pay');
    Route::get('/credit', [AccountController::class, 'credit'])->name('credit');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
});

/*
|--------------------------------------------------------------------------
| Payment gateway
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/paystack', PaystackWebhookController::class)
    ->middleware('throttle:webhooks')
    ->name('webhooks.paystack');

if (! app()->isProduction() && config('josveggies.payments.gateway') === 'fake') {
    Route::middleware('auth')->group(function () {
        Route::get('/dev/fake-pay/{reference}', [FakePayController::class, 'show'])->middleware('signed')->name('dev.fake-pay.show');
        Route::post('/dev/fake-pay/{reference}', [FakePayController::class, 'complete'])->name('dev.fake-pay.complete');
    });
}

/*
|--------------------------------------------------------------------------
| Operations Console (staff only, 2FA enforced by the "staff" middleware)
|--------------------------------------------------------------------------
*/

Route::get('/console/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('console.login');

Route::middleware(['auth', 'staff'])->prefix('console')->name('console.')->group(function () {
    Route::get('/', Console\DashboardController::class)->name('dashboard');
    Route::get('/security', [Console\SecurityController::class, 'show'])->name('security');

    // Runs: everyone on staff can see them; what they can see and do is role-filtered.
    Route::get('/runs', [Console\RunController::class, 'index'])->name('runs.index');
    Route::get('/runs/{run}', [Console\RunController::class, 'show'])->name('runs.show')->whereNumber('run');
    Route::post('/runs/{run}/transition', [Console\RunController::class, 'transition'])->name('runs.transition');
    Route::post('/runs/{run}/purchased', [Console\RunController::class, 'purchased'])->middleware('role:admin,jos_ops')->name('runs.purchased');

    Route::middleware('role:admin')->group(function () {
        Route::get('/runs/create', [Console\RunController::class, 'create'])->name('runs.create');
        Route::post('/runs', [Console\RunController::class, 'store'])->name('runs.store');
        Route::get('/runs/{run}/edit', [Console\RunController::class, 'edit'])->name('runs.edit');
        Route::put('/runs/{run}', [Console\RunController::class, 'update'])->name('runs.update');

        Route::prefix('catalogue')->name('catalogue.')->group(function () {
            Route::resource('boxes', Console\BoxTemplateController::class)->except(['show', 'destroy'])->parameters(['boxes' => 'box']);
            Route::resource('items', Console\ItemController::class)->except(['show', 'destroy']);
            Route::resource('pack-units', Console\PackUnitController::class)->except(['show', 'destroy'])->parameters(['pack-units' => 'packUnit']);
            Route::resource('add-ons', Console\AddOnController::class)->except(['show', 'destroy'])->parameters(['add-ons' => 'addOn']);
        });

        Route::get('/zones', [Console\ZoneController::class, 'index'])->name('zones.index');
        Route::post('/zones', [Console\ZoneController::class, 'store'])->name('zones.store');
        Route::put('/zones/{zone}', [Console\ZoneController::class, 'update'])->name('zones.update');
        Route::put('/hubs/{hub}', [Console\ZoneController::class, 'updateHub'])->name('hubs.update');

        Route::middleware('password.confirm')->group(function () {
            Route::get('/staff', [Console\StaffController::class, 'index'])->name('staff.index');
            Route::post('/staff', [Console\StaffController::class, 'store'])->name('staff.store');
            Route::put('/staff/{user}', [Console\StaffController::class, 'update'])->name('staff.update');
            Route::get('/settings', [Console\SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [Console\SettingsController::class, 'update'])->name('settings.update');
            Route::put('/policies/{slug}', [Console\SettingsController::class, 'updatePolicy'])->name('policies.update');
        });

        Route::get('/reports', [Console\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/runs/{run}', [Console\ReportController::class, 'run'])->name('reports.run');
        Route::post('/reports/runs/{run}/costs', [Console\ReportController::class, 'storeCost'])->name('reports.costs.store');
        Route::get('/audit', Console\AuditController::class)->name('audit');
    });

    Route::middleware('role:admin,jos_ops')->prefix('jos')->name('jos.')->group(function () {
        Route::get('/runs/{run}/procurement', [Console\JosController::class, 'procurement'])->name('procurement');
        Route::get('/runs/{run}/procurement.pdf', [Console\JosController::class, 'procurementPdf'])->name('procurement.pdf');
        Route::get('/runs/{run}/packing', [Console\JosController::class, 'packing'])->name('packing');
        Route::get('/runs/{run}/packing.pdf', [Console\JosController::class, 'packingPdf'])->name('packing.pdf');
        Route::get('/runs/{run}/labels.pdf', [Console\JosController::class, 'labelsPdf'])->name('labels.pdf');
        Route::get('/runs/{run}/substitutions', [Console\JosController::class, 'substitutions'])->name('substitutions');
    });

    Route::middleware('role:admin,abuja_ops')->prefix('abuja')->name('abuja.')->group(function () {
        Route::get('/pickup', [Console\AbujaController::class, 'pickup'])->name('pickup');
        Route::get('/dispatch', [Console\AbujaController::class, 'dispatch'])->name('dispatch');
        Route::get('/runs/{run}/forfeitures', [Console\AbujaController::class, 'forfeitures'])->name('forfeitures');
    });

    Route::middleware('role:admin,abuja_ops')->group(function () {
        Route::get('/riders', [Console\RiderController::class, 'index'])->name('riders.index');
        Route::post('/riders', [Console\RiderController::class, 'store'])->name('riders.store');
        Route::put('/riders/{rider}', [Console\RiderController::class, 'update'])->name('riders.update');
    });
});
