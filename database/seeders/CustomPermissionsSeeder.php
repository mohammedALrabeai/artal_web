<?php

namespace Database\Seeders;



use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class CustomPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'edit_employee_status',
            'edit_employee_bank',
           
            'export_excel',
            // أضف المزيد هنا حسب الحاجة
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        $this->command->info('✅ تم إنشاء أو تحديث الصلاحيات بنجاح.');
    }
}





// php artisan db:seed --class=CustomPermissionsSeeder
