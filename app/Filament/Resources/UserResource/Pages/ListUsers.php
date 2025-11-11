<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Widgets\UsersOverviewWidget;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
    protected static ?string $title = 'Pengguna';
    protected static ?string $breadcrumb = 'List';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Pengguna'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UsersOverviewWidget::class,
        ];
    }
}
