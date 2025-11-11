<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasAuditTrail;

class Menu extends Model
{
    use HasFactory;
    use HasAuditTrail;

    protected $fillable = ['label', 'url', 'icon', 'parent_id', 'sort', 'isactive', 'created_by', 'updated_by', 'context'];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->where('isactive', 'Y')->orderBy('sort');
    }
}
