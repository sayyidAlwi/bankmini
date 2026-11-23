<?php

namespace App\Providers;

use App\Models\Shift;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\View;
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
        View::composer('layouts.dashboard', function ($view): void {
            $shiftMingguIni = null;

            if (auth()->check()) {
                $shiftMingguIni = Shift::query()
                    ->whereDate(
                        'tanggal_mulai',
                        CarbonImmutable::instance(now())->startOfWeek(CarbonImmutable::MONDAY)->toDateString(),
                    )
                    ->first();
            }

            $view->with('shiftMingguIni', $shiftMingguIni);
        });
    }
}
