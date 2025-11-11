<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;

class UsersOverviewWidget extends Widget
{
    protected static string $view = 'filament.widgets.users-overview-widget';

    public function getColumnSpan(): int | string | array
    {
        return 'full';
    }

    protected function getViewData(): array
    {
        return [
            'totalUsers' => User::where('id', '>', 1)->count(),
            'activeUsers' => User::where('id', '>', 1)->where('isactive', 'Y')->count(),
            'inactiveUsers' => User::where('id', '>', 1)->where('isactive', 'N')->count(),
        ];
    }
}
