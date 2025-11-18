<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use App\Models\Article;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;

class Articles extends Page
{
    use WithPagination;

    protected static string $resource = ArticleResource::class;
    protected static string $view = 'filament.pages.articles';
    protected static ?string $title = 'Artikel';
    protected static ?string $breadcrumb = 'List';

    public ?int $articleIdToDelete = null;
    public $search = '';

    protected $queryString = ['search'];

    public function getArticlesProperty()
    {
        return Article::query()
            ->with(['category', 'author']) // supaya data relasi ikut diambil
            ->when($this->search, function ($query) {
                $search = "%{$this->search}%";

                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', $search)
                        ->orWhere('excerpt', 'like', $search)
                        ->orWhereHas('category', fn($c) => $c->where('name', 'like', $search))
                        ->orWhereJsonContains('tags', $this->search);
                });
            })
            ->latest()
            ->paginate(9);
    }

    public function confirmDelete($id)
    {
        $this->articleIdToDelete = $id;
        $this->dispatch('open-modal', id: 'confirm-delete');
    }

    public function deleteArticle()
    {
        if (!$this->articleIdToDelete) return;

        $article = Article::find($this->articleIdToDelete);

        if (!$article) {
            Notification::make()
                ->title('Artikel tidak ditemukan')
                ->danger()
                ->send();
            return;
        }

        $article->delete();

        Notification::make()
            ->title('Artikel dan lampiran berhasil dihapus')
            ->success()
            ->send();

        $this->dispatch('close-modal', id: 'confirm-delete');
    }

    public function like($id): void
    {
        $article = Article::find($id);

        if ($article) {
            $article->increment('likes');
            Notification::make()
                ->title('Anda menyukai artikel ini!')
                ->success()
                ->send();
        }
    }

    public function viewArticle($id): void
    {
        $this->article = Article::find($id);
        $this->redirect(ArticleResource::getUrl('view', ['record' => $id]));
    }
}
