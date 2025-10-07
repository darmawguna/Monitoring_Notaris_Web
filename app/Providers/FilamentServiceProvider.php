<?php

namespace App\Providers;

use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Force HTTPS for all URLs in production/staging
        if (app()->environment(['production', 'staging'])) {
            URL::forceScheme('https');
        }

        // Ensure Filament assets use HTTPS
        FilamentAsset::register([
            Js::make('custom-select', resource_path('js/custom-select.js'))
                ->loadedOnRequest(),
        ]);
    }
}