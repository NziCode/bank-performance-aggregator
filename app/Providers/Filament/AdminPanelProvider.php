<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\PerformanceChart;
use App\Filament\Widgets\StatsOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->brandName('سامانه ارزیابی عملکرد')
            ->darkMode(false)
            ->navigationGroups([
                NavigationGroup::make('عملکرد'),
                NavigationGroup::make('مدیریت'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
                StatsOverview::class,
                PerformanceChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->spa()
            ->renderHook(
                'panels::head.end',
                fn() => '
                <link href="https://fonts.bunny.net/css?family=vazirmatn:400,500,600,700&display=swap" rel="stylesheet">
                <style>
                    body, .fi-body, .fi-main, .fi-sidebar, .fi-topbar {
                        direction: rtl;
                        font-family: Vazirmatn, sans-serif;
                    }
                    select {
                        direction: rtl;
                        text-align: right;
                        background-position: left 0.5rem center !important;
                        padding-right: 0.75rem !important;
                        padding-left: 2rem !important;
                    }
                    .fi-select-input {
                        direction: rtl;
                        text-align: right;
                    }
                </style>'
            );
    }
}
