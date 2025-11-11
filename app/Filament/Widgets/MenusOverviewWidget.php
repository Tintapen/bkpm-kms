<?php

namespace App\Filament\Widgets;

use App\Models\Menu;
use Filament\Widgets\Widget;

class MenusOverviewWidget extends Widget
{
    protected static string $view = 'filament.widgets.menus-overview-widget';

    public function getColumnSpan(): int | string | array
    {
        return 'full';
    }

    protected function getViewData(): array
    {
        return [
            'totalMenus' => Menu::count(),
            'activeMenus' => Menu::where('isactive', 'Y')->count(),
            'inactiveMenus' => Menu::where('isactive', 'N')->count(),
            'mainMenus' => Menu::whereNull('parent_id')->count(),
        ];
    }
}
