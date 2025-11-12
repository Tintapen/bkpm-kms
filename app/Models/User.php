<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'isactive',
        'emailnotif',
        'articleupdate',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function hasRoleContext(string $context): bool
    {
        return $this->roles()->where('context', $context)->exists();
    }

    protected static function booted()
    {
        static::created(function ($user) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'tambah',
                'subject_type' => 'Pengguna',
                'subject_id' => $user->id,
                'description' => 'Menambah pengguna: ' . $user->name,
            ]);
        });
        static::updated(function ($user) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'ubah',
                'subject_type' => 'Pengguna',
                'subject_id' => $user->id,
                'description' => 'Mengubah pengguna: ' . $user->name,
            ]);
        });
        static::deleted(function ($user) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'hapus',
                'subject_type' => 'Pengguna',
                'subject_id' => $user->id,
                'description' => 'Menghapus pengguna: ' . $user->name,
            ]);
        });
    }
}
