<?php

namespace Database\Seeders;

use App\Enums\Bank;
use App\Enums\BloodType;
use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Enums\InsuranceType;
use App\Enums\ContractType;
use App\Enums\JobTitle;
use App\Enums\ParentInsurance;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 5; $i++) {
            Employee::create([
                'first_name' => $faker->firstName,
                'father_name' => $faker->lastName,
                'grandfather_name' => $faker->lastName,
                'family_name' => $faker->lastName,
                'birth_date' => $faker->date,
                'national_id' => $faker->unique()->randomNumber(8),
                'national_id_expiry' => $faker->date,
                'nationality' => $faker->country,
                'bank_account' => $faker->bankAccountNumber,
                'blood_type' => BloodType::cases()[array_rand(BloodType::cases())]->value, // Random BloodType
                'contract_start' => $faker->date,
                'contract_end' => $faker->date,
                'actual_start' => $faker->date,
                'basic_salary' => $faker->numberBetween(3000, 10000),
                'living_allowance' => $faker->numberBetween(500, 2000),
                'other_allowances' => $faker->numberBetween(100, 1000),
                'job_status' => $faker->randomElement(['active', 'inactive']),
                'health_insurance_company' => $faker->company,
                'social_security' => $faker->boolean,
                'social_security_code' => $faker->randomNumber(8),
                'qualification' => $faker->randomElement(['Bachelor', 'Master', 'PhD']),
                'specialization' => $faker->jobTitle,
                'mobile_number' => $faker->phoneNumber,
                'phone_number' => $faker->phoneNumber,
                'region' => $faker->state,
                'city' => $faker->city,
                'street' => $faker->streetName,
                'building_number' => $faker->buildingNumber,
                'apartment_number' => $faker->randomNumber(3),
                'postal_code' => $faker->postcode,
                'facebook' => $faker->url,
                'twitter' => $faker->url,
                'linkedin' => $faker->url,
                'email' => 'employee' . $i . '@demo.com',
                'password' => Hash::make('demo1234'), // Default password
                'added_by' => $faker->numberBetween(1, 4),
                'status' => 1,
                'onesignal_player_id' => $faker->uuid,
                'remember_token' => Str::random(10),
                'api_token' => $faker->uuid,
                'leave_balance' => $faker->numberBetween(0, 30),
                'out_of_zone' => $faker->boolean,
                'insurance_company_id' => $faker->numberBetween(1, 3),
                'parent_insurance' => ParentInsurance::cases()[array_rand(ParentInsurance::cases())]->value,
                'insurance_company_name' => $faker->company,
                'commercial_record_id' => $faker->numberBetween(1, 10),
               'job_title' => JobTitle::cases()[array_rand(JobTitle::cases())]->value, // Random JobTitle
                'bank_name' => Bank::cases()[array_rand(Bank::cases())]->value, // Random Bank
                'insurance_type' => $faker->randomElement(InsuranceType::cases())->value,
                'insurance_number' => $faker->randomNumber(8),
                'insurance_start_date' => $faker->date,
                'insurance_end_date' => $faker->date,
                'contract_type' => $faker->randomElement(ContractType::cases())->value,
            ]);
        }
    }
}
