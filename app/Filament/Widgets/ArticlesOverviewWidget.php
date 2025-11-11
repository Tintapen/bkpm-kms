<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\Category;
use Filament\Widgets\Widget;

class ArticlesOverviewWidget extends Widget
{
    protected static string $view = 'filament.widgets.articles-overview-widget';

    public function getColumnSpan(): int | string | array
    {
        return 'full';
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        return [
            'isViewer' => $user?->hasRoleContext('Viewer') ?? false,
        ];
    }

    public function getTotalArticles(): int
    {
        return Article::count();
    }

    public function getTotalCategory(): int
    {
        return Category::count();
    }

    public function getTotalViews(): int
    {
        return Article::sum('views') ?? 0;
    }

    public function getRecentlyViewedArticles()
    {
        return Article::orderBy('updated_at', 'desc')->take(5)->get();
    }

    public function getTopArticles()
    {
        return Article::orderBy('views', 'desc')->take(5)->get();
    }
}
