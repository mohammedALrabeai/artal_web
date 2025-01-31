<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure user exists or create and assign role
        $this->createOrUpdateUser('user@demo.com', 'user');
        $this->createOrUpdateUser('hr@demo.com', 'hr');
        $this->createOrUpdateUser('manager@demo.com', 'manager');
        $this->createOrUpdateUser('general_manager@demo.com', 'general_manager');
    }

    /**
     * Create or update user and assign role.
     *
     * @param string $email
     * @param string $roleName
     */
    protected function createOrUpdateUser(string $email, string $roleName): void
    {
        // Check if user already exists
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => ucfirst(explode('@', $email)[0])] // Set the name from the email if it doesn't exist
        );

        // Assign the role to the user
        $user->assignRole($roleName);
    }
}
