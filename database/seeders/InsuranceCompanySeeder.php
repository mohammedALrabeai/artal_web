<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InsuranceCompany;
use Carbon\Carbon;

class InsuranceCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $insuranceCompanies = [
            [
                'name' => 'Global Insurance Co.',
                'activation_date' => Carbon::now()->subYears(2)->toDateString(),
                'expiration_date' => Carbon::now()->addYears(1)->toDateString(),
                'policy_number' => 'POLICY123456',
                'branch' => 'Main Branch',
                'is_active' => true,
            ],
            [
                'name' => 'Health First Inc.',
                'activation_date' => Carbon::now()->subYear()->toDateString(),
                'expiration_date' => Carbon::now()->addYears(2)->toDateString(),
                'policy_number' => 'POLICY654321',
                'branch' => 'North Branch',
                'is_active' => true,
            ],
            [
                'name' => 'SecureLife Insurance',
                'activation_date' => Carbon::now()->subMonths(6)->toDateString(),
                'expiration_date' => Carbon::now()->addYears(3)->toDateString(),
                'policy_number' => 'POLICY987654',
                'branch' => 'East Branch',
                'is_active' => false,
            ],
        ];

        foreach ($insuranceCompanies as $company) {
            InsuranceCompany::create($company);
        }
    }
}
