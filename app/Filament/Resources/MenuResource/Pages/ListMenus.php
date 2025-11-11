<?php

namespace App\Filament\Resources\MenuResource\Pages;

use App\Filament\Widgets\MenusOverviewWidget;
use App\Filament\Resources\MenuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;
    protected static ?string $title = 'Menu';
    protected static ?string $breadcrumb = 'List';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Menu'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MenusOverviewWidget::class,
        ];
    }
}
