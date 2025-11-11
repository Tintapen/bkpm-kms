<?php

namespace App\Models;

use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    use HasAuditTrail;

    protected $fillable = ['name', 'isactive', 'level', 'parent_id'];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function scopeLeaf($query)
    {
        return $query->whereDoesntHave('children');
    }

    public function topLevelCategory(): self
    {
        $current = $this;
        while ($current->parent) {
            $current = $current->parent;
        }
        return $current;
    }

    public function fullNameCategory(): string
    {
        $names = [];
        $current = $this;
        while ($current) {
            array_unshift($names, $current->name);
            $current = $current->parent;
        }

        $label = implode(' > ', $names);

        return $label;
    }

    public static function getAllChildCategories($parentIds, &$collectedIds = [])
    {
        $children = Category::whereIn('parent_id', $parentIds)->pluck('id');

        if ($children->isEmpty()) {
            return;
        }

        // Simpan anak-anak ke daftar akhir
        foreach ($children as $childId) {
            if (!in_array($childId, $collectedIds)) {
                $collectedIds[] = $childId;
            }
        }

        self::getAllChildCategories($children, $collectedIds);
    }

    public function childrenRecursive()
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->with(['childrenRecursive' => fn($q) => $q->where('isactive', 'Y')->orderBy('name')]);
    }
    public function articles()
    {
        return $this->hasMany(Article::class, 'category_id')
            ->where('isactive', 'Y')
            ->orderBy('title');
    }

    public function loadRecursiveWithArticles()
    {
        $this->load('articles', 'children');

        foreach ($this->children as $child) {
            $child->loadRecursiveWithArticles();
        }
    }

    // Get total articles for this category and all descendants
    public function getTotalArticlesRecursive()
    {
        $count = $this->articles->count();
        foreach ($this->children as $child) {
            $count += $child->getTotalArticlesRecursive();
        }
        return $count;
    }
}
