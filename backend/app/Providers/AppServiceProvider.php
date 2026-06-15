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
            $key = 'ip:'.$request->ip();

            return [
                Limit::perMinute(12)->by($key),
                Limit::perMinute(30)->by('scan-ip:'.$request->ip()),
            ];
        });
    }
}
