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

    protected function deleteAllAttachments(\App\Models\Article $article): void
    {
        $disk = $article->attachment_disk ?? 'public';
        $storage = \Illuminate\Support\Facades\Storage::disk($disk);

        // 1️⃣ Hapus file attachment utama jika ada
        if (!empty($article->attachment) && $storage->exists($article->attachment)) {
            $storage->delete($article->attachment);
            Log::info("Attachment utama dihapus: {$article->attachment}");
        }

        // 2️⃣ Hapus semua file yang disebut di excerpt
        if (!empty($article->excerpt)) {
            // Tangkap semua URL file dari storage/articles/...
            preg_match_all('/storage\/articles\/([^"\s>]+)/i', $article->excerpt, $matches);

            foreach ($matches[1] as $file) {
                $relativePath = 'articles/' . ltrim($file, '/');

                if ($storage->exists($relativePath)) {
                    $storage->delete($relativePath);
                    Log::info("Attachment dari excerpt dihapus: {$relativePath}");
                }
            }

            // 3️⃣ Tangkap juga dari JSON data-trix-attachment (jika ada)
            if (preg_match_all('/data-trix-attachment="([^"]+)"/i', $article->excerpt, $jsonMatches)) {
                foreach ($jsonMatches[1] as $encoded) {
                    $decoded = html_entity_decode($encoded);
                    $data = json_decode($decoded, true);

                    if (is_array($data)) {
                        $candidateUrl = $data['url'] ?? $data['href'] ?? null;
                        if ($candidateUrl && preg_match('/storage\/articles\/([^"\s>]+)/i', $candidateUrl, $m)) {
                            $rel = 'articles/' . ltrim($m[1], '/');
                            if ($storage->exists($rel)) {
                                $storage->delete($rel);
                                Log::info("Attachment dari JSON dihapus: {$rel}");
                            }
                        }
                    }
                }
            }
        }
    }
}
