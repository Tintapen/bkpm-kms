<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends BaseResource
{
    protected static ?string $model = User::class;
    protected static ?string $pluralModelLabel = 'Pengguna';

    public static function form(Form $form): Form
    {
        /** @var User $user */
        $user = auth()->user();

        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama Pengguna')
                    ->required()
                    ->maxLength(100),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required(fn(string $context): bool => $context === 'create')
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if ($record) {
                            $component->state('********');
                        }
                    })
                    ->dehydrateStateUsing(function ($state, $record) {
                        // Jika create, hash password apapun yang diisi
                        if (!$record) {
                            return $state ? Hash::make($state) : null;
                        }
                        // Jika edit, hanya update jika password diubah dari '********'
                        if ($state && $state !== '********') {
                            return Hash::make($state);
                        }
                        // Jika tidak diubah, return null agar tidak update
                        return null;
                    })
                    ->dehydrated(fn($state) => $state !== null && $state !== '********')
                    ->same('password_confirmation')
                    ->validationAttribute('Password'),
                TextInput::make('password_confirmation')
                    ->label('Konfirmasi Password')
                    ->password()
                    ->required(fn(callable $get) => filled($get('password')) && $get('password') !== '********')
                    ->validationAttribute('Konfirmasi Password'),
                Select::make('role')
                    ->label('Role')
                    ->options(Role::pluck('name', 'id'))
                    ->placeholder('Pilih Role')
                    ->searchable()
                    ->required()
                    ->default(null)
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if ($record) {
                            $component->state($record->roles()->first()?->id);
                        }
                    })
                    ->reactive(),
                Toggle::make('emailnotif')
                    ->label('Notifikasi Email')
                    ->inline(false)
                    ->default(true)
                    ->formatStateUsing(fn($state) => $state === 'Y' || $state === true || is_null($state))
                    ->dehydrateStateUsing(fn($state) => $state ? 'Y' : 'N'),
                Toggle::make('isactive')
                    ->label('Status')
                    ->inline(false)
                    ->default(true)
                    ->formatStateUsing(fn($state) => $state === 'Y' || $state === true || is_null($state))
                    ->dehydrateStateUsing(fn($state) => $state ? 'Y' : 'N'),
                Toggle::make('articleupdate')
                    ->label('Update Artikel')
                    ->inline(false)
                    ->default(true)
                    ->formatStateUsing(fn($state) => $state === 'Y' || $state === true || is_null($state))
                    ->dehydrateStateUsing(fn($state) => $state ? 'Y' : 'N'),
            ]);
    }

    public static function table(Table $table): Table
    {
        /** @var Table $table */
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Pengguna')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('role.name')
                    ->label('Hak Akses')
                    ->getStateUsing(fn($record) => $record->roles->first()?->name ?? '-')
                    ->searchable(),
                BadgeColumn::make('emailnotif')
                    ->label('Notif Email')
                    ->formatStateUsing(fn(string $state): string => $state === 'Y' ? 'Ya' : 'Tidak')
                    ->color(fn(string $state): string => $state === 'Y' ? 'success' : 'danger'),
                BadgeColumn::make('articleupdate')
                    ->label('Update Artikel')
                    ->formatStateUsing(fn(string $state): string => $state === 'Y' ? 'Ya' : 'Tidak')
                    ->color(fn(string $state): string => $state === 'Y' ? 'success' : 'danger'),
                BadgeColumn::make('isactive')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): string => $state === 'Y' ? 'Aktif' : 'Nonaktif')
                    ->color(fn(string $state): string => $state === 'Y' ? 'success' : 'danger'),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => static::canEdit($record)),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn($record) => static::canDelete($record))
                    ->modalHeading('Hapus Pengguna')
                    ->modalDescription('Apakah Anda yakin ingin menghapus Pengguna ini?')
                    ->modalSubmitActionLabel('Hapus')
                    ->modalCancelActionLabel('Batal'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('id', '>', 1); // 👈 ini menyembunyikan user 1
    }
}
