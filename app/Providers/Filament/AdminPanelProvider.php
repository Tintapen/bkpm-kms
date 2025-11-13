<?php

namespace App\Providers\Filament;

use Filament\Enums\ThemeMode;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Pages\Home;

class AdminPanelProvider extends PanelProvider
{
    protected function getDefaultPage(): string
    {
        return Home::class;
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->renderHook(
                'panels::styles.after',
                fn(): string => $this->getRoleBasedStyle()
            )
            ->brandName('KMS SPB')
            // ->defaultThemeMode(ThemeMode::Dark)
            ->darkMode(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Home::class
            ])
            ->spa()
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // TotalCategoriesWidget::class,
                // TotalCategoriesWidget::class,
            ])
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
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    protected function getRoleBasedStyle(): string
    {
        return <<<HTML
    <style>
        :root {
            --filament-dark-bg: #111828;
            --filament-light-bg: #ffffff;
            --filament-body-bg: #F9FAFB;
        }

        body {
            background-color: var(--filament-body-bg) !important;
        }

        .fi-sidebar {
            background-color: var(--filament-light-bg) !important;
        }
        .fi-sidebar .fi-sidebar-item {
            font-size: 1.15rem !important;
        }
        .fi-sidebar .fi-sidebar-item .fi-sidebar-item-icon {
            font-size: 1.6rem !important;
            width: 2.1rem !important;
            height: 2.1rem !important;
        }
        .dark .fi-topbar,
        .dark .fi-header {
            background-color: var(--filament-dark-bg) !important;
        }
    </style>
    HTML;
    }
}
