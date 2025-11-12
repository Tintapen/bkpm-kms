<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;
use Filament\Actions\Action;
use Illuminate\Support\Js;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
    protected static ?string $title = 'Ubah Pengguna';
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
        $roleId = $this->form->getState()['role'] ?? null;

        if ($roleId) {
            $role = Role::findById($roleId); // Lebih aman karena otomatis pakai guard
            $this->record->syncRoles([$role]);
        } else {
            $this->record->syncRoles([]); // Jika tidak ada role, hapus semua
        }
    }

    /**
     * Force redirect to the user list after creating a user.
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
