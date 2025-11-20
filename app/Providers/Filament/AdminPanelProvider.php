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
            ->sidebarCollapsibleOnDesktop(true)
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
            --custom-topbar-bg: #183776;
            --custom-sidebar-header-bg: #183776;
            --custom-topbar-input-bg: #20488e;
            --custom-topbar-input-text: #fff;
            --custom-topbar-input-placeholder: #e0e7ef;
            --custom-topbar-input-border: #20488e;
            --custom-topbar-icon: #fff;
            --custom-topbar-icon-hover: #e0e7ef;
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
        /* Custom topbar and sidebar header color */
        .fi-topbar, .custom-topbar, .fi-sidebar-header, .custom-sidebar-header {
            background-color: var(--custom-topbar-bg) !important;
            color: #fff !important;
        }
        .fi-sidebar-header, .custom-sidebar-header {
            background-color: var(--custom-sidebar-header-bg) !important;
            color: #fff !important;
        }
        .fi-sidebar-header .fi-sidebar-header-title, .fi-sidebar-header .fi-sidebar-header-title *,
        .fi-topbar .fi-topbar-heading, .fi-topbar .fi-topbar-heading *,
        .fi-topbar .fi-brand, .fi-topbar .fi-brand *,
        .fi-topbar .brand, .fi-topbar .brand *,
        .fi-logo, .fi-logo * {
            color: #fff !important;
            font-weight: bold !important;
        }
        .fi-topbar nav, .fi-topbar .bg-white {
            background-color: transparent !important;
        }
        /* Topbar search input custom style */
        .fi-topbar input[type="text"],
        .fi-topbar input[type="search"] {
            background: var(--custom-topbar-input-bg) !important;
            color: var(--custom-topbar-input-text) !important;
            border-color: var(--custom-topbar-input-border) !important;
        }
        .fi-topbar input[type="text"]::placeholder,
        .fi-topbar input[type="search"]::placeholder {
            color: var(--custom-topbar-input-placeholder) !important;
            opacity: 1 !important;
        }
        .fi-topbar input[type="text"]:focus,
        .fi-topbar input[type="search"]:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 2px #2563eb33 !important;
        }
        /* Sidebar collapse/expand button color */
        .fi-topbar .fi-icon-btn,
        .fi-topbar .fi-icon-btn .fi-icon-btn-icon,
        .fi-topbar .fi-icon-btn svg {
            color: var(--custom-topbar-icon) !important;
            fill: var(--custom-topbar-icon) !important;
            stroke: var(--custom-topbar-icon) !important;
        }
        .fi-topbar .fi-icon-btn:hover,
        .fi-topbar .fi-icon-btn:focus {
            color: var(--custom-topbar-icon-hover) !important;
            fill: var(--custom-topbar-icon-hover) !important;
            stroke: var(--custom-topbar-icon-hover) !important;
        }
        .fi-topbar .fi-icon-btn .fi-icon-btn-icon,
        .fi-topbar .fi-icon-btn svg path {
            color: var(--custom-topbar-icon) !important;
            fill: var(--custom-topbar-icon) !important;
            stroke: var(--custom-topbar-icon) !important;
        }
        .fi-topbar .fi-icon-btn:hover .fi-icon-btn-icon,
        .fi-topbar .fi-icon-btn:focus .fi-icon-btn-icon,
        .fi-topbar .fi-icon-btn:hover svg path,
        .fi-topbar .fi-icon-btn:focus svg path {
            color: var(--custom-topbar-icon-hover) !important;
            fill: var(--custom-topbar-icon-hover) !important;
            stroke: var(--custom-topbar-icon-hover) !important;
        }
        /* Notification bell icon in topbar */
        .fi-topbar button > svg,
        .fi-topbar button > svg path {
            color: var(--custom-topbar-icon) !important;
            fill: var(--custom-topbar-icon) !important;
            stroke: var(--custom-topbar-icon) !important;
        }
        .fi-topbar button:hover > svg,
        .fi-topbar button:focus > svg,
        .fi-topbar button:hover > svg path,
        .fi-topbar button:focus > svg path {
            color: var(--custom-topbar-icon-hover) !important;
            fill: var(--custom-topbar-icon-hover) !important;
            stroke: var(--custom-topbar-icon-hover) !important;
        }
        .dark .fi-topbar,
        .dark .fi-header {
            background-color: var(--filament-dark-bg) !important;
        }
    </style>
    HTML;
    }
}
