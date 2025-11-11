<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use App\Models\User;

class PermissionHelper
{
    public static function checkPermission(string $action, ?string $url): bool
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$url || !$user) {
            return false;
        }

        $path = trim(parse_url($url, PHP_URL_PATH), '/');

        // Hapus "admin" jika di awal URL
        $segments = explode('/', $path);
        if ($segments[0] === 'admin') {
            array_shift($segments);
        }

        // Ambil slug terakhir
        $baseSlug = $segments[0] ?? 'admin';
        $slug = Str::slug($baseSlug);

        $permission = "{$action}_{$slug}";

        return auth()->check() && $user->can($permission);
    }
}
