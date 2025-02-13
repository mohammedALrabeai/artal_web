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
     */
    protected function createOrUpdateUser(string $email, string $roleName): void
    {
        // Check if user already exists
        $user = User::firstOrCreate(
            ['email' => $email], // الشرط للبحث عن المستخدم
            [
                'password' => bcrypt('12345678'),
                'name' => ucfirst(explode('@', $email)[0]), // توليد الاسم تلقائيًا من الإيميل
            ]
        );

        // Assign the role to the user
        $user->assignRole($roleName);
    }
}
