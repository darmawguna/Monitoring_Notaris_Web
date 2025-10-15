<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Pages\Laporan;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\BerkasTerbaru;
use App\Filament\Widgets\KwitansiTerbaru;
use App\Filament\Widgets\PerbankanWidget;
use Filament\Support\Facades\FilamentView;

// TODO lakukan stress test untuk mengukur kekuatan dari sistem
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->databaseNotifications()
            // ->colors([
            //     'primary' => Color::Amber,
            // ])
            ->brandLogo(asset('images/logo-complete.svg'))


            // 2. Mengatur favicon (ikon di tab browser)
            ->favicon(asset('images/logo.svg'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                PerbankanWidget::class,
                StatsOverview::class,
                BerkasTerbaru::class,
                KwitansiTerbaru::class,
                \App\Filament\Widgets\TugasBerkasMasukWidget::class,
                \App\Filament\Widgets\TugasPerbankanMasukWidget::class,
                \App\Filament\Widgets\TugasTurunWarisMasukWidget::class,
                \App\Filament\Widgets\BerkasSelesaiWidget::class,
            ])
            ->renderHook('panels::topbar.start', fn() => view('filament.partials.navbar-title'))
            ->renderHook('panels::body.start', fn() => view('filament.partials.sidebar-styles'))
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
