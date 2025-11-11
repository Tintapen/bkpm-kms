<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('menus')->insert([
            [
                'label' => 'Home',
                'icon'  => 'heroicon-o-home',
                'url'   => '/admin',
                'sort'  => 1,
                'context' => 'All',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'label' => 'Artikel',
                'icon'  => 'heroicon-o-document-text',
                'url' => '/admin/articles',
                'sort' => 2,
                'context' => 'Admin',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'label'  => 'Kategori',
                'icon'  => 'heroicon-o-rectangle-stack',
                'url'    => '/admin/categories',
                'sort' => 3,
                'context' => 'Admin',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'label' => 'Pengaturan',
                'icon' => '',
                'url'  => NULL,
                'sort' => 4,
                'context' => 'Admin',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $settingsMenu = DB::table('menus')->where('label', 'Pengaturan')->first();

        if ($settingsMenu) {
            DB::table('menus')->insert([
                [
                    'label'     => 'Pengaturan Email',
                    'icon'      => 'heroicon-o-envelope',
                    'url'       => '/admin/mail-settings',
                    'parent_id' => $settingsMenu->id,
                    'sort'      => 1,
                    'context'   => 'Admin',
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'label'     => 'Menu',
                    'icon'      => 'heroicon-o-adjustments-horizontal',
                    'url'       => '/admin/menus',
                    'parent_id' => $settingsMenu->id,
                    'sort'      => 2,
                    'context'   => 'Admin',
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'label'     => 'Users',
                    'icon'      => 'heroicon-o-user',
                    'url'       => '/admin/users',
                    'parent_id' => $settingsMenu->id,
                    'sort'      => 3,
                    'context'   => 'Admin',
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'label'      => 'Roles',
                    'icon'      => 'heroicon-o-shield-check',
                    'url'       => '/admin/roles',
                    'parent_id' => $settingsMenu->id,
                    'sort'      => 4,
                    'context'   => 'Admin',
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
