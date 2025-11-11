<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ArticlesOverviewWidget;
use Filament\Pages\Dashboard;

class Home extends Dashboard
{
    protected static ?string $title = 'Home';
    protected static ?string $slug = '';

    public function getWidgets(?int $columns = 2): array
    {
        return [
            ArticlesOverviewWidget::class,
        ];
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
