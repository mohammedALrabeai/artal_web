<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        Role::create(['name' => 'user', 'level' => 1]);
        Role::create(['name' => 'hr', 'level' => 2]);
        Role::create(['name' => 'manager', 'level' => 3]);
        Role::create(['name' => 'general_manager', 'level' => 4]);
    }
}
