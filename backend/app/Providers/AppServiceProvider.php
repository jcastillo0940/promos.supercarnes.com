<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('invoice-scan', function (Request $request) {
            $key = 'invoice-scan:'.$request->ip().':'.substr((string) $request->userAgent(), 0, 120);

            return [
                Limit::perMinute(6)->by($key),
                Limit::perHour(25)->by($request->ip()),
                Limit::perDay(120)->by('scan-day:'.$request->ip()),
            ];
        });
    }
}
