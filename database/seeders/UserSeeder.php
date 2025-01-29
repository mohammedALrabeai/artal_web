<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'user',
            'email' => 'user@demo.com',
            'role_id' => 1
        ]);
        User::factory()->create([
            'name' => 'hr',
            'email' => 'hr@demo.com',
            'role_id' => 2
        ]);
        User::factory()->create([
            'name' => 'manager',
            'email' => 'manager@demo.com',
            'role_id' => 3
        ]);
        User::factory()->create([
            'name' => 'general manager',
            'email' => 'general_manager@demo.com',
            'role_id' => 4
        ]);
    }
}
