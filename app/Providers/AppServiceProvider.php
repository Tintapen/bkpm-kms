<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Menu;
use App\Models\MailSetting;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Pagination\Paginator;
use App\Helpers\PermissionHelper;
use App\Http\Livewire\NotificationBell;
use App\Http\Livewire\Sidebar;
use Livewire\Livewire;

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
        $this->registerFilamentNavigation();
        $this->configureMailSettings();
        $this->panelSettings();
        Paginator::useBootstrapFive();
        Livewire::component('notification-bell', NotificationBell::class);
        Livewire::component('sidebar', Sidebar::class);
    }

    protected function registerFilamentNavigation(): void
    {
        Filament::serving(function () {
            $user = auth()->user();
            $userContext = null;
            if ($user && method_exists($user, 'roles')) {
                $role = $user->roles()->first();
                $userContext = $role->context ?? null;
            }

            $menus = Menu::with(['children' => function ($query) use ($userContext) {
                $query->where('isactive', 'Y')->orderBy('sort');
                if ($userContext && $userContext !== 'All') {
                    $query->whereIn('context', ['All', $userContext]);
                }
            }])
                ->where('isactive', 'Y')
                ->whereNull('parent_id')
                ->orderBy('sort');
            if ($userContext && $userContext !== 'All') {
                $menus = $menus->whereIn('context', ['All', $userContext]);
            }
            $menus = $menus->get();

            $groups = [];
            $items = [];

            foreach ($menus as $menu) {
                if ($menu->children->isNotEmpty()) {
                    $groups[] = NavigationGroup::make()
                        ->label($menu->label)
                        ->collapsed();

                    foreach ($menu->children as $child) {
                        if (!PermissionHelper::checkPermission('view', $child->url)) {
                            continue;
                        }

                        $items[] = NavigationItem::make()
                            ->label($child->label)
                            ->url(fn() => url($child->url))
                            ->group($menu->label)
                            ->sort($child->sort)
                            ->icon($child->icon ?? 'heroicon-o-document-text')
                            ->isActiveWhen(fn() => request()->is(trim(parse_url($child->url, PHP_URL_PATH), '/') . '*'));
                    }
                } else {
                    if (!PermissionHelper::checkPermission('view', $menu->url)) {
                        continue;
                    }

                    $items[] = NavigationItem::make()
                        ->label($menu->label)
                        ->icon($menu->icon ?? 'heroicon-o-document-text')
                        ->url(fn() => url($menu->url))
                        ->sort($menu->sort)
                        ->isActiveWhen(function () use ($menu) {
                            $menuPath = trim(parse_url($menu->url, PHP_URL_PATH), '/');
                            $currentPath = trim(request()->path(), '/');

                            if ($menuPath === 'admin') return $currentPath === 'admin';

                            return str_starts_with($currentPath, $menuPath);
                        });
                }
            }

            Filament::registerNavigationGroups($groups);
            Filament::registerNavigationItems($items);
        });
    }

    protected function configureMailSettings(): void
    {
        if (!Schema::hasTable('mail_settings')) {
            return;
        }

        $mail = MailSetting::first();

        if (!$mail) {
            return;
        }

        config([
            'mail.default' => $mail->mailer,
            'mail.mailers.smtp.host' => $mail->host,
            'mail.mailers.smtp.port' => $mail->port,
            'mail.mailers.smtp.username' => $mail->username,
            'mail.mailers.smtp.password' => $mail->password,
            'mail.mailers.smtp.encryption' => $mail->encryption,
            'mail.from.address' => $mail->from_address,
            'mail.from.name' => $mail->from_name,
        ]);
    }

    protected function panelSettings(): void
    {
        Filament::serving(function () {
            Filament::registerRenderHook(
                'panels::topbar.start',
                fn(): string => view('components.custom-topbar')->render(),
            );

            // Filament::registerRenderHook(
            //     'panels::sidebar.nav.start',
            //     fn(): string => view('components.sidebar')->render(),
            // );
            // // Render the Livewire upload progress component at the end of the body
            // Filament::registerRenderHook(
            //     'panels::body.end',
            //     fn(): string => view('components.livewire-upload-progress')->render(),
            // );

            Filament::registerRenderHook('panels::sidebar.nav.end', function (): string {
                return view('components.sidebar-categories')->render();
            });
        });
    }
}
