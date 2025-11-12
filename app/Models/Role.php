<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'context',
    ];

    protected static function booted()
    {
        static::created(function ($role) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'tambah',
                'subject_type' => 'Role',
                'subject_id' => $role->id,
                'description' => 'Menambah role: ' . $role->name,
            ]);
        });
        static::updated(function ($role) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'ubah',
                'subject_type' => 'Role',
                'subject_id' => $role->id,
                'description' => 'Mengubah role: ' . $role->name,
            ]);
        });
        static::deleted(function ($role) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'hapus',
                'subject_type' => 'Role',
                'subject_id' => $role->id,
                'description' => 'Menghapus role: ' . $role->name,
            ]);
        });
    }
}
