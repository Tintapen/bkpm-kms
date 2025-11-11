<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use App\Models\Article;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ArticleViewerPage extends Page
{
    protected static string $resource = ArticleResource::class;

    protected static string $view = 'filament.pages.article-viewer-page';
    protected static ?string $title = 'Detail Artikel';
    protected static ?string $breadcrumb = 'Detail';

    public $article;

    public function mount($record)
    {
        $this->article = Article::findOrFail($record);
        $sessionKey = 'article_viewed_' . $record;

        if (!session()->has($sessionKey)) {
            $this->article->increment('views');
            session()->put($sessionKey, true);
            $this->article->refresh();
        }
    }

    public function selectArticle($id)
    {
        $this->article = Article::findOrFail($id);
    }

    public function like($id)
    {
        $article = Article::find($id);

        if ($article) {
            $article->increment('likes');

            if ($this->article->id == $id) {
                $this->article->likes = $article->likes;
            }

            Notification::make()
                ->title('Anda menyukai artikel ini!')
                ->success()
                ->send();
        }
    }
}
