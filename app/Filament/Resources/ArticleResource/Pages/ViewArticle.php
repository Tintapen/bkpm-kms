<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewArticle extends ViewRecord
{
    protected static string $resource = ArticleResource::class;
    protected static ?string $title = 'Detail Artikel';
    protected static ?string $breadcrumb = 'Detail';

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Hanya tampilkan tombol Edit jika user punya izin edit
        if (static::getResource()::canEdit($this->record)) {
            $actions[] = EditAction::make()
                ->label('Edit')
                ->color('primary');
        }

        return $actions;
    }
}
