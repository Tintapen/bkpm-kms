<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Menu;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class RoleResource extends BaseResource
{
    protected static ?string $model = Role::class;
    protected static ?string $pluralModelLabel = 'Role';

    public static function ensurePermissionsExist(array $permissions): void
    {
        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }
    }

    public static function form(Form $form): Form
    {
        // Map menu key to allowed actions for dynamic permission generation
        $menuActionsMap = [
            'articles' => ['view', 'create', 'update', 'delete', 'download'],
        ];
        $actions = ['view', 'create', 'update', 'delete', 'download'];
        $actionsIDN = ['Lihat', 'Buat', 'Ubah', 'Hapus', 'Unduh'];
        $actionsDescIDN = [
            'view' => 'Melihat',
            'create' => 'Membuat',
            'update' => 'Mengubah',
            'delete' => 'Menghapus',
            'download' => 'Mengunduh',
        ];

        // Custom actions for Home menu
        $homeActions = ['statistik_dashboard', 'ringkasan_aktivitas', 'aktivitas_terbaru'];
        $homeActionsIDN = ['Statistik Dashboard', 'Ringkasan Aktivitas', 'Aktivitas Terbaru'];
        $homeActionsDescIDN = [
            'statistik_dashboard' => 'Lihat Statistik Dashboard',
            'ringkasan_aktivitas' => 'Lihat Ringkasan Aktivitas',
            'aktivitas_terbaru' => 'Lihat Aktivitas Terbaru',
        ];

        $menus = Menu::with('parent')
            ->where('isactive', 'Y')
            ->where(function ($query) {
                $query->whereNotNull('parent_id')
                    ->orWhereDoesntHave('children');
            })
            ->orderBy('label')
            ->get();

        $allPermissions = [];
        $permissionCards = collect();

        foreach ($menus as $menu) {
            $label = $menu->label;
            $keySource = $menu->url ? Str::after($menu->url, '/admin/') : $label;
            $key = Str::slug($keySource);

            if (!$key) {
                continue;
            }

            $permissionOptions = [];
            $permissionDescriptions = [];
            if (strtolower($label) === 'home') {
                foreach ($homeActions as $i => $action) {
                    $permission = "{$action}_{$key}";
                    $permissionOptions[$permission] = $homeActionsIDN[$i] ?? ucfirst($action);
                    $permissionDescriptions[$permission] = $homeActionsDescIDN[$action];
                    $allPermissions[] = $permission;
                }
            } else {
                $allowedActions = $menuActionsMap[$key] ?? ['view', 'create', 'update', 'delete'];
                foreach ($allowedActions as $action) {
                    $i = array_search($action, $actions);
                    $permission = "{$action}_{$key}";
                    $permissionOptions[$permission] = $actionsIDN[$i] ?? ucfirst($action);
                    $desc = $actionsDescIDN[$action] ?? ucfirst($action);
                    $permissionDescriptions[$permission] = $desc . ' ' . $label;
                    $allPermissions[] = $permission;
                }
            }

            $permissionCards->push(
                Card::make()->schema([
                    Placeholder::make($label),
                    CheckboxList::make("permissions_group_{$key}")
                        ->label('')
                        ->options($permissionOptions)
                        ->columns(2)
                        ->bulkToggleable()
                        ->descriptions($permissionDescriptions)
                        ->extraAttributes(['class' => 'permission-checkboxlist'])
                        ->afterStateHydrated(function (callable $set, ?Model $record) use ($key, $actions, $label, $homeActions) {
                            if (!$record) {
                                return;
                            }

                            $assignedPermissions = $record->permissions->pluck('name')->toArray();

                            $groupActions = (strtolower($label) === 'home') ? $homeActions : $actions;
                            $groupPermissions = collect($groupActions)
                                ->map(fn($action) => "{$action}_{$key}")
                                ->filter(fn($perm) => in_array($perm, $assignedPermissions))
                                ->values()
                                ->toArray();

                            $set("permissions_group_{$key}", $groupPermissions);
                        })
                ])->columnSpan(1)
            );
        }

        self::ensurePermissionsExist($allPermissions);

        return $form->schema([
            Grid::make(2)->schema([
                TextInput::make('name')
                    ->label('Nama Role')
                    ->required(),
                Select::make('context')
                    ->label('Konteks')
                    ->options([
                        'Admin' => 'Admin',
                        'Viewer' => 'Viewer',
                    ])
                    ->searchable()
                    ->required(),
                Toggle::make('select_all')
                    ->label('Pilih Semua')
                    ->helperText('Pilih semua permission untuk hak akses ini.')
                    ->reactive()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (callable $set, callable $get) use ($allPermissions) {
                        $selected = $get('permissions_state') ?? [];
                        $set('select_all', empty(array_diff($allPermissions, $selected)));
                    })
                    ->afterStateUpdated(function ($state, callable $set, callable $get) use ($menus, $actions) {
                        $allPermissions = [];

                        foreach ($menus as $menu) {
                            $keySource = $menu->url ? Str::after($menu->url, '/admin/') : $menu->label;
                            $key = Str::slug($keySource);
                            if (!$key) {
                                continue;
                            }

                            $groupPermissions = collect($actions)->map(fn($action) => "{$action}_{$key}")->toArray();
                            $set("permissions_group_{$key}", $state ? $groupPermissions : []);

                            if ($state) {
                                $allPermissions = array_merge($allPermissions, $groupPermissions);
                            }
                        }

                        $set('permissions_state', $state ? array_unique($allPermissions) : []);
                    }),
            ]),

            Grid::make(3)->schema($permissionCards->toArray()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Role')
                    ->searchable(),
                TextColumn::make('permissions.name')
                    ->badge()
                    ->label('Permissions')
                    ->separator(', '),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => static::canEdit($record)),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn($record) => static::canDelete($record))
                    ->modalHeading('Hapus Role')
                    ->modalDescription('Apakah Anda yakin ingin menghapus role ini?')
                    ->modalSubmitActionLabel('Hapus')
                    ->modalCancelActionLabel('Batal'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
