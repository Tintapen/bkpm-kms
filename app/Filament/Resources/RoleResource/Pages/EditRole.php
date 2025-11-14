<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Js;
use Illuminate\Support\Str;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;
    protected static ?string $title = 'Ubah Role';
    protected static ?string $breadcrumb = 'Ubah';

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

    protected function afterSave(): void
    {
        $permissions = [];

        foreach ($this->data as $key => $value) {
            if (Str::startsWith($key, 'permissions_group_') && is_array($value)) {
                $permissions = array_merge($permissions, $value);
            }
        }

        $this->record->syncPermissions($permissions);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Hapus field virtual yang tidak perlu disimpan ke tabel
        return collect($data)
            ->reject(fn($_, $key) => $key === 'permissions_state' || str($key)->startsWith('permissions_group_'))
            ->toArray();
    }

    /**
     * Force redirect to the role list after creating a role.
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
