<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'John Cyrus Patungan',
            'email' => 'cy@gmail.com',
            'password' => bcrypt('developer')
        ]);

        $permission = Permission::create([
            'name' => 'can login'
        ]);

        $user->givePermissionTo($permission);
        $user->syncRoles(['super admin']);

        $user = User::factory()->create([
            'name' => 'Jelai Marie Recierdo',
            'email' => 'jelai@gmail.com',
            'password' => bcrypt('developer')
        ]);

        $user->givePermissionTo($permission);
        $user->syncRoles(['super admin']);
    }
}
