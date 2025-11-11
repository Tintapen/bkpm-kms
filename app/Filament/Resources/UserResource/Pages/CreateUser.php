<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Js;
use Spatie\Permission\Models\Role;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected static ?string $title = 'Tambah Pengguna';
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

    /**
     * Force redirect to the user list after creating a user.
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $roleId = $this->form->getState()['role'] ?? null;
        if ($roleId) {
            $role = Role::findById($roleId);
            $this->record->syncRoles([$role]);
        } else {
            $this->record->syncRoles([]);
        }
    }
}
