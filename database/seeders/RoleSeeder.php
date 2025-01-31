<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Create roles using Spatie's Role model
        Role::create(['name' => 'user']);
        Role::create(['name' => 'hr']);
        Role::create(['name' => 'manager']);
        Role::create(['name' => 'general_manager']);
    }
}
