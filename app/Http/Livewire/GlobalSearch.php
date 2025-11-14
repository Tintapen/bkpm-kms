<?php

namespace App\Http\Livewire;

use App\Models\Article;
use Livewire\Component;

class GlobalSearch extends Component
{
    public $searchQuery = '';
    public $results = [];

    public function updatedSearchQuery($value)
    {
        // Jika search terlalu pendek, kosongkan hasil
        if (strlen($value) < 2) {
            $this->results = [];
            return;
        }

        $search = "%{$value}%";

        $articles = Article::query()
            ->with(['category', 'author'])
            ->where(function ($q) use ($search, $value) {
                $q->where('title', 'like', $search)
                    ->orWhere('excerpt', 'like', $search)
                    ->orWhereHas('category', function ($c) use ($search) {
                        $c->where('name', 'like', $search);
                    })
                    ->orWhereJsonContains('tags', $value);
            })
            ->limit(10)
            ->get();

        $this->results = $articles->map(function ($a) {
            return [
                'id'    => $a->id,
                'label' => $a->title,
            ];
        })->toArray();
    }

    public function selectItem($id)
    {
        $article = Article::find($id);
        if (! $article) return;

        $url = \App\Filament\Resources\ArticleResource::getUrl('view', ['record' => $article]);
        return redirect()->to($url);
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}
