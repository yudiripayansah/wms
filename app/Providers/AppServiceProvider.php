<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Filament::serving(function () {
            Filament::registerStyles([
                'filament-forms'  => asset('css/filament-forms.css'),
                'tippy'           => asset('css/tippy.css'),
                'tippy-light'     => asset('css/tippy-light.css'),
            ]);

            Filament::registerTheme(asset('css/filament.css'));
        });
    }
}
