<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

class FilePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'can upload file'
            ],
            [
                'name' => 'can download file'
            ],
            [
                'name' => 'can delete file'
            ],
            [
                'name' => 'can view file'
            ],
            [
                'name' => 'can create file'
            ],
            [
                'name' => 'can rename file'
            ],
            [
                'name' => 'can move file and folder'
            ],
            [
                'name' => 'can share file'
            ]
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate($permission);
        }

        Artisan::call('cache:clear');
    }
}
