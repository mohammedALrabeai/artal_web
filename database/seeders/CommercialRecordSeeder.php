<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommercialRecord;
use App\Models\InsuranceCompany;
use Carbon\Carbon;

class CommercialRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Sample insurance companies for relationships
        $insuranceCompanies = InsuranceCompany::pluck('id')->toArray();

        // Parent commercial records
        $parentRecords = CommercialRecord::factory()
            ->count(2)
            ->create([
                'insurance_company_id' => fn () => $insuranceCompanies[array_rand($insuranceCompanies)],
            ]);

        // Child commercial records for each parent
        foreach ($parentRecords as $parent) {
            CommercialRecord::factory()
                ->count(4) // Three child records per parent
                ->create([
                    'parent_company_id' => $parent->id,
                    'insurance_company_id' => fn () => $insuranceCompanies[array_rand($insuranceCompanies)],
                ]);
        }
    }
}
