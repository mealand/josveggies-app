<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaystackGateway;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;
use App\Services\Sms\TermiiSmsSender;
use App\Support\Phone;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function ($app): PaymentGateway {
            $gateway = config('josveggies.payments.gateway');

            if ($gateway === 'paystack') {
                return new PaystackGateway((string) config('services.paystack.secret_key'), (string) config('services.paystack.base_url'));
            }

            if ($app->isProduction()) {
                throw new RuntimeException('The fake payment gateway cannot be used in production.');
            }

            return new FakeGateway;
        });

        $this->app->singleton(SmsSender::class, fn (): SmsSender => config('josveggies.sms.driver') === 'termii'
            ? new TermiiSmsSender(
                (string) config('services.termii.api_key'),
                (string) config('services.termii.sender_id'),
                (string) config('services.termii.channel'),
                (string) config('services.termii.base_url'),
            )
            : new LogSmsSender);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->numbers()->uncompromised()
            : Password::min(10)->letters()->numbers());

        $this->configureGates();
        $this->configureRateLimiting();
        $this->logNotifications();
    }

    private function configureGates(): void
    {
        Gate::define('view-market-day', fn (User $user): bool => $user->canSeeMarketDay());
        Gate::define('admin', fn (User $user): bool => $user->hasRole(Role::Admin));
        Gate::define('jos-ops', fn (User $user): bool => $user->hasRole(Role::Admin, Role::JosOps));
        Gate::define('abuja-ops', fn (User $user): bool => $user->hasRole(Role::Admin, Role::AbujaOps));
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by((string) $request->user()?->id ?: $request->ip()));
        RateLimiter::for('pickup-verify', fn (Request $request) => Limit::perMinute(30)->by((string) $request->user()?->id));
        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('waitlist', fn (Request $request) => Limit::perMinute(5)->by((string) $request->user()?->id ?: $request->ip()));
    }

    /**
     * Record every notification attempt (masked recipient) for support and auditing.
     */
    private function logNotifications(): void
    {
        $record = function (object $notifiable, object $notification, string $channel, string $status, ?string $error = null): void {
            if (! $notifiable instanceof User) {
                return;
            }

            NotificationLog::query()->create([
                'user_id' => $notifiable->id,
                'channel' => $channel === SmsChannel::class ? 'sms' : $channel,
                'event' => class_basename($notification).(property_exists($notification, 'event') ? ':'.$notification->event : ''),
                'recipient' => $channel === SmsChannel::class ? Phone::mask(Phone::normalize($notifiable->phone)) : preg_replace('/(?<=.).(?=[^@]*@)/', '*', $notifiable->email),
                'status' => $status,
                'error' => $error,
            ]);
        };

        Event::listen(NotificationSent::class, fn (NotificationSent $e) => $record($e->notifiable, $e->notification, $e->channel, 'sent'));
        Event::listen(NotificationFailed::class, fn (NotificationFailed $e) => $record($e->notifiable, $e->notification, $e->channel, 'failed', json_encode($e->data)));
    }
}
