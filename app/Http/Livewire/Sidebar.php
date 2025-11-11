<?php

namespace App\Http\Livewire;

use App\Models\Article;
use Livewire\Component;
use App\Models\Category;

class Sidebar extends Component
{
    public array $open = [];
    public string $search = '';
    public $selectedCategoryId = null;

    public function toggle($id)
    {
        $this->selectedCategoryId = $id;
        if (in_array($id, $this->open)) {
            $this->open = array_diff($this->open, [$id]);
        } else {
            $this->open[] = $id;
        }
    }

    public function render()
    {
        $user = auth()->user();

        // Cek role context
        if (! $user || ! $user->hasRoleContext('Viewer')) {
            return view('livewire.sidebar')->with('categories', collect());
        }

        $categoriesQuery = Category::with(['childrenRecursive', 'articles'])
            ->where('isactive', 'Y')
            ->whereNull('parent_id')
            ->orderBy('name');

        if ($this->search) {
            $categoriesQuery->where('name', 'like', "%{$this->search}%");
        }

        $categories = $categoriesQuery->get();

        foreach ($categories as $cat) {
            $cat->loadRecursiveWithArticles();
        }

        // --- Tambahan: buka folder parent kategori jika sedang lihat detail artikel ---
        $activeArticleId = request()->route('record') ?? null;
        if ($activeArticleId) {
            $article = Article::find($activeArticleId);
            if ($article && $article->category) {
                $category = $article->category;
                $openIds = [];
                // Loop ke atas, masukkan semua parent id ke $openIds
                while ($category) {
                    $openIds[] = $category->id;
                    $category = $category->parent;
                }
                // Urutkan dari root ke leaf
                $openIds = array_reverse($openIds);
                $this->open = array_unique(array_merge($this->open, $openIds));
            }
        }

        return view('livewire.sidebar', compact('categories'));
    }

    public function redirectToCategory($slug)
    {
        return redirect()->to('/admin/articles?search=' . $slug);
    }
}
