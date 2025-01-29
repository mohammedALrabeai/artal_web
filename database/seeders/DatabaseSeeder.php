<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

         // Clear files
         Storage::deleteDirectory('app/public');
        //  Storage::deleteDirectory('app/private');

    $this->call([
        RoleSeeder::class,
        UserSeeder::class,
        RequestTypeSeeder::class,
        PolicySeeder::class,
        ApprovalFlowSeeder::class,
        InsuranceCompanySeeder::class,
        CommercialRecordSeeder::class,
        EmployeeSeeder::class,
    ]);
}
}
