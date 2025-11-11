<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Js;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;
    protected static ?string $title = 'Tambah Artikel';
    protected static ?string $breadcrumb = 'Tambah';

    /**
     * Override the default Create button to use Indonesian label and blue (primary) color.
     */
    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label('Simpan')
            ->submit('create')
            ->keyBindings(['mod+s'])
            ->color('primary');
    }

    /**
     * Override the default Cancel button to use Indonesian label and red (danger) color.
     */
    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label('Batal')
            ->alpineClickHandler('document.referrer ? window.history.back() : (window.location.href = ' . Js::from($this->previousUrl ?? static::getResource()::getUrl()) . ')')
            ->color('danger');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCancelFormAction(),
            $this->getCreateFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return url('/admin/articles'); // arahkan ke page custom
    }
}
