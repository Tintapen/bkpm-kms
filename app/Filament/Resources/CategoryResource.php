<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
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

class CategoryResource extends BaseResource
{
    protected static ?string $model = Category::class;
    protected static ?string $pluralModelLabel = 'Kategori';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama Kategori')
                    ->required(),
                Toggle::make('isactive')
                    ->label('Status')
                    ->inline(false)
                    ->default(true)
                    ->formatStateUsing(fn($state) => $state === 'Y' || $state === true || is_null($state))
                    ->dehydrateStateUsing(fn($state) => $state ? 'Y' : 'N')
                    ->required(),
                Select::make('level')
                    ->label('Level')
                    ->options(collect(range(1, 4))->mapWithKeys(fn($i) => [$i => "$i"]))
                    ->live()
                    ->afterStateHydrated(function (Forms\Set $set, $state) {
                        // Pastikan parent_id null jika level 1 saat load form
                        if ((int) $state === 1) {
                            $set('parent_id', null);
                        }
                    })
                    ->placeholder('Pilih Level Kategori')
                    ->searchable()
                    ->required(),
                Select::make('parent_id')
                    ->label('Parent')
                    ->options(function (Forms\Get $get, ?Category $record) {
                        $level = $get('level');

                        // Jika level 1, tidak perlu parent, maka return kosong
                        if (!$level || $level == 1) {
                            return [];
                        }

                        // Query untuk level di bawah kategori yang dipilih
                        $query = Category::where('level', $level - 1)
                            ->where('isactive', 'Y');

                        // Jika sedang edit, dan ada parent_id, kecualikan kategori yang sudah dipilih
                        if ($record) {
                            $selectedParentId = $record->id;

                            // Mengecualikan kategori yang dipilih
                            if ($selectedParentId) {
                                $query->where('id', '!=', $selectedParentId);
                            }
                        }

                        return $query->pluck('name', 'id');
                    })
                    ->placeholder('Pilih Kategori Parent')
                    ->required(fn(Forms\Get $get) => (int)$get('level') > 1)
                    ->rules(function (Forms\Get $get) {
                        return [
                            function ($attribute, $value, $fail) use ($get) {
                                $level = (int) $get('level'); // Atur level dari form

                                if ($level > 1 && !$value) {
                                    $fail('Parent must be filled in for levels greater than 1.');
                                }

                                if ($level <= 1 && $value) {
                                    $fail('Level 1 cannot have a parent.');
                                }
                            }
                        ];
                    })
                    ->reactive()
                    ->visible(fn(Forms\Get $get) => (int)$get('level') > 1)
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->searchable(),
                TextColumn::make('level')
                    ->searchable(),
                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->searchable(),
                BadgeColumn::make('isactive')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): string => $state === 'Y' ? 'Aktif' : 'Nonaktif')
                    ->color(fn(string $state): string => $state === 'Y' ? 'success' : 'danger'),
            ])
            ->defaultSort('name')
            ->defaultSort('level')
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
                    ->modalHeading('Hapus Kategori')
                    ->modalDescription('Apakah Anda yakin ingin menghapus kategori ini?')
                    ->modalSubmitActionLabel('Hapus')
                    ->modalCancelActionLabel('Batal'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
