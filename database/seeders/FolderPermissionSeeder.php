<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

class FolderPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'can upload folder'
            ],
            [
                'name' => 'can download folder'
            ],
            [
                'name' => 'can delete folder'
            ],
            [
                'name' => 'can view folder'
            ],
            [
                'name' => 'can create folder'
            ],
            [
                'name' => 'can rename folder'
            ],
            [
                'name' => 'can move folder and folder'
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate($permission);
        }

        Artisan::call('cache:clear');
    }
}
