<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Paginator::defaultView('components.pagination');

        // Never serve plain HTTP in production.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Ministry and site-office networks are shared, so the per-network cap is generous;
        // the tight caps are per phone number and per email address. Every submission emails
        // a confirmation to the address given, so the email cap also stops the forms being used
        // to send mail to strangers.
        RateLimiter::for('forms', function (Request $request) {
            $phone = preg_replace('/\D+/', '', input_string($request->input('phone')));
            $email = strtolower(input_string($request->input('email')));
            $limits = [Limit::perMinute(20)->by('ip|'.$request->ip()), Limit::perHour(60)->by('ip-hour|'.$request->ip())];
            if ($phone !== '') {
                $limits[] = Limit::perMinute(5)->by('phone|'.$phone.'|'.$request->ip());
            }
            if ($email !== '') {
                $limits[] = Limit::perHour(10)->by('email|'.$email);
            }

            return $limits;
        });
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower(input_string($request->input('email'))).'|'.$request->ip()));
    }
}
