<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Aksi dasar
        $actions = ['view', 'create', 'update', 'delete'];
        $articlesActions = ['view', 'create', 'update', 'delete', 'download'];
        $homeActions = ['statistik_dashboard', 'ringkasan_aktivitas', 'aktivitas_terbaru'];

        // 2. Master menu
        $menus = Menu::with('parent')
            ->where('isactive', 'Y')
            ->where(function ($query) {
                $query->whereNotNull('parent_id')
                    ->orWhereDoesntHave('children');
            })
            ->orderBy('label')
            ->get();

        // 3. Buat semua permission
        foreach ($menus as $menu) {
            $label = $menu->label;
            $keySource = $menu->url ? Str::after($menu->url, '/admin/') : $label;
            $key = Str::slug($keySource);

            if (strtolower($label) === 'home') {
                foreach ($homeActions as $action) {
                    Permission::firstOrCreate([
                        'name' => "{$action}_{$key}",
                        'guard_name' => 'web',
                    ]);
                }
            } elseif ($key === 'articles') {
                foreach ($articlesActions as $action) {
                    Permission::firstOrCreate([
                        'name' => "{$action}_{$key}",
                        'guard_name' => 'web',
                    ]);
                }
            } else {
                foreach ($actions as $action) {
                    Permission::firstOrCreate([
                        'name' => "{$action}_{$key}",
                        'guard_name' => 'web',
                    ]);
                }
            }
        }

        // 4. Buat role
        $superAdmin = Role::firstOrCreate(['name' => 'superadmin', 'context' => 'Admin']);

        // 5. Assign semua permission ke admin
        $superAdmin->syncPermissions(Permission::all());

        // 6. Assign role ke user pertama (id=1)
        $user = User::whereIn('id', [1])->get();
        if ($user) {
            foreach ($user as $user) {
                $user->syncRoles($superAdmin);
            }
        }
    }
}
