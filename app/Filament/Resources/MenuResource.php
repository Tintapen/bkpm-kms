<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuResource\Pages;
use App\Filament\Resources\MenuResource\RelationManagers;
use App\Models\Menu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Models\Reference;
use App\Models\ReferenceDetail;

class MenuResource extends BaseResource
{
    protected static ?string $model = Menu::class;
    protected static ?string $pluralModelLabel = 'Menu';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('label')
                    ->label('Nama Menu')
                    ->required(),
                Toggle::make('isactive')
                    ->label('Status')
                    ->inline(false)
                    ->default(true)
                    ->formatStateUsing(fn($state) => $state === 'Y' || $state === true || is_null($state))
                    ->dehydrateStateUsing(fn($state) => $state ? 'Y' : 'N'),
                TextInput::make('url')
                    ->label('URL')
                    ->nullable(),
                TextInput::make('icon')
                    ->label('Ikon')
                    ->nullable(),
                Select::make('parent_id')
                    ->label('Parent')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'label',
                        modifyQueryUsing: function ($query) {
                            $query->whereNull('parent_id');
                        }
                    )
                    ->placeholder('Pilih Parent')
                    ->searchable()
                    ->preload(),
                TextInput::make('sort')->numeric()->default(0),
                Select::make('context')
                    ->label('Konteks')
                    ->options([
                        'All' => 'All',
                        'Admin' => 'Admin',
                        'Viewer' => 'Viewer',
                    ])
                    ->default('All')
                    ->searchable()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Nama Menu')
                    ->searchable(),
                TextColumn::make('parent.label')
                    ->label('Parent')
                    ->searchable(),
                TextColumn::make('url')
                    ->searchable(),
                TextColumn::make('icon')
                    ->label('Ikon'),
                TextColumn::make('sort')
                    ->label('Urutan')
                    ->sortable(),
                BadgeColumn::make('isactive')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): string => $state === 'Y' ? 'Aktif' : 'Nonaktif')
                    ->color(fn(string $state): string => $state === 'Y' ? 'success' : 'danger'),
            ])
            ->defaultSort('label')
            ->filters([
                SelectFilter::make('isactive')
                    ->label('Status')
                    ->options([
                        'Y' => 'Aktif',
                        'N' => 'Nonaktif',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => static::canEdit($record)),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn($record) => static::canDelete($record))
                    ->modalHeading('Hapus Menu')
                    ->modalDescription('Apakah Anda yakin ingin menghapus Menu ini?')
                    ->modalSubmitActionLabel('Hapus')
                    ->modalCancelActionLabel('Batal'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
