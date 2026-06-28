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
                <link href="https://fonts.bunny.net/css?family=vazirmatn:400,500,600,700,800&display=swap" rel="stylesheet">
                <style>
                    * {
                        font-family: Vazirmatn, sans-serif !important;
                    }

                    :root {
                        --fi-primary: #1e40af;
                    }

                    /* سایدبار */
                    .fi-sidebar {
                        background: linear-gradient(180deg, #1e3a5f 0%, #1e40af 100%) !important;
                    }
                    .fi-sidebar-nav-groups {
                        padding: 0.5rem;
                    }

                    /* آیتم‌های منو — پس‌زمینه شفاف */
                    .fi-sidebar-item a,
                    .fi-sidebar-item button {
                        background: transparent !important;
                        border-radius: 0.5rem;
                    }
                    .fi-sidebar-item.fi-active a,
                    .fi-sidebar-item.fi-active button {
                        background: rgba(255,255,255,0.2) !important;
                    }
                    .fi-sidebar-item:hover a,
                    .fi-sidebar-item:hover button {
                        background: rgba(255,255,255,0.12) !important;
                    }

                    .fi-sidebar-item-label {
                        color: #ffffff !important;
                        font-weight: 500;
                    }
                    .fi-sidebar-item.fi-active .fi-sidebar-item-label {
                        font-weight: 700;
                    }
                    .fi-sidebar-group-label {
                        color: #bfdbfe !important;
                        font-size: 0.7rem;
                        font-weight: 700;
                        letter-spacing: 0.05em;
                    }
                    .fi-sidebar-item svg {
                        color: #bfdbfe !important;
                    }
                    .fi-sidebar-item.fi-active svg {
                        color: #ffffff !important;
                    }
                    .fi-sidebar-nav-group-collapse-button {
                        color: #bfdbfe !important;
                        background: transparent !important;
                    }
                    .fi-sidebar-nav-group-collapse-button svg {
                        color: #bfdbfe !important;
                    }

                    /* هدر */
                    .fi-topbar {
                        background: #ffffff !important;
                        border-bottom: 1px solid #e2e8f0 !important;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
                    }

                    /* برند */
                    .fi-logo {
                        color: #1e40af !important;
                        font-weight: 800 !important;
                        font-size: 1.1rem !important;
                    }

                    /* کارت‌های widget */
                    .fi-wi-stats-overview-stat {
                        border-radius: 1rem !important;
                        border: none !important;
                        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.07) !important;
                        transition: transform 0.2s, box-shadow 0.2s;
                    }
                    .fi-wi-stats-overview-stat:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 15px -3px rgba(0,0,0,0.1) !important;
                    }
                    .fi-wi-stats-overview-stat-value {
                        font-size: 2rem !important;
                        font-weight: 800 !important;
                    }

                    /* جدول */
                    .fi-ta-table {
                        border-radius: 0.75rem !important;
                        overflow: hidden;
                    }
                    .fi-ta-header-cell {
                        background: #f8fafc !important;
                        font-weight: 700 !important;
                        color: #374151 !important;
                    }
                    .fi-ta-row:hover td {
                        background: #f0f7ff !important;
                    }

                    /* دکمه‌ها */
                    .fi-btn-primary {
                        background: linear-gradient(135deg, #1e40af, #3b82f6) !important;
                        border: none !important;
                        border-radius: 0.6rem !important;
                        font-weight: 600 !important;
                        box-shadow: 0 2px 8px rgba(30,64,175,0.3) !important;
                        transition: all 0.2s !important;
                    }
                    .fi-btn-primary:hover {
                        transform: translateY(-1px) !important;
                        box-shadow: 0 4px 12px rgba(30,64,175,0.4) !important;
                    }

                    /* فرم */
                    .fi-input {
                        border-radius: 0.6rem !important;
                        border-color: #e2e8f0 !important;
                        transition: border-color 0.2s, box-shadow 0.2s !important;
                    }
                    .fi-input:focus {
                        border-color: #3b82f6 !important;
                        box-shadow: 0 0 0 3px rgba(59,130,246,0.1) !important;
                    }

                    /* badge */
                    .fi-badge {
                        border-radius: 99px !important;
                        font-weight: 600 !important;
                        font-size: 0.7rem !important;
                    }

                    /* پس‌زمینه */
                    .fi-main {
                        background: #f8fafc !important;
                    }

                    /* select */
                    select {
                        border-radius: 0.6rem !important;
                    }
                </style>'
            );
    }
}
