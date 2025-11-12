<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use App\Models\Category;
use Faker\Provider\Base;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Select;
use ZipStream\File;

class ArticleResource extends BaseResource
{
    protected static ?string $model = Article::class;
    protected static ?string $pluralModelLabel = 'Artikel';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label('Judul Artikel')
                    ->required()
                    ->maxLength(255),
                Select::make('category_id')
                    ->label('Kategori')
                    ->options(function () {
                        return Category::with([
                            'parent',
                            'parent.parent',
                            'parent.parent.parent',
                            'parent.parent.parent.parent',
                        ])
                            ->leaf()
                            ->where('isactive', 'Y')
                            ->get()
                            ->mapWithKeys(fn($cat) => [
                                $cat->id => $cat->fullNameCategory(),
                            ])
                            ->sort()
                            ->toArray();
                    })
                    ->placeholder('Pilih Kategori')
                    ->searchable()
                    ->required(),
                TagsInput::make('tags'),
                FileUpload::make('attachment'),
                RichEditor::make('excerpt')
                    ->label('Konten Artikel')
                    ->required()
                    ->fileAttachmentsDirectory('articles/tmp')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsVisibility('public')
                    ->saveUploadedFileAttachmentsUsing(function ($file) {
                        // Allowed MIME types: pdf, csv, excel, word, powerpoint
                        $allowed = [
                            'application/pdf',
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        ];

                        $mime = $file->getMimeType() ?: $file->getClientMimeType();
                        $maxBytes = 50 * 1024 * 1024;
                        try {
                            $size = $file->getSize() ?? ($file->getMaxFilesize ? $file->getMaxFilesize() : null);
                        } catch (\Throwable $e) {
                            $size = null;
                        }

                        if ($size === null) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'excerpt' => __('Gagal membaca ukuran file. Silakan coba file lain.'),
                            ]);
                        }
                        if ($size > $maxBytes) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'excerpt' => __('Ukuran file melebihi batas 50 MB.'),
                            ]);
                        }
                        if (! in_array($mime, $allowed, true)) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'excerpt' => __('Hanya file PDF, Excel, Word, PowerPoint, CSV yang diizinkan.'),
                            ]);
                        }
                        if (! $file->isValid()) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'excerpt' => __('File upload gagal. Silakan coba lagi.'),
                            ]);
                        }

                        $originalName = $file->getClientOriginalName() ?? $file->getFilename();
                        $filename = uniqid() . '-' . str_replace(' ', '_', basename($originalName));

                        return $file->storePubliclyAs('articles/tmp', $filename, 'public');
                    }),
            ])->columns(1);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Articles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
            'view' => Pages\ArticleViewerPage::route('/{record}'),
        ];
    }
}
