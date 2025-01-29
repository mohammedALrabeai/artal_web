<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CommercialRecord>
 */
class CommercialRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'record_number' => strtoupper($this->faker->unique()->bothify('CR-#####')),
            'entity_name' => $this->faker->company,
            'city' => $this->faker->city,
            'entity_type' => $this->faker->randomElement(['Sole Proprietorship', 'Partnership', 'Corporation']),
            'capital' => $this->faker->numberBetween(100000, 5000000),
            'insurance_number' => strtoupper($this->faker->bothify('INS-#####')),
            'labour_office_number' => strtoupper($this->faker->bothify('LAB-#####')),
            'unified_number' => $this->faker->unique()->randomNumber(9, true),
            'expiry_date_hijri' => Carbon::now()->addYears(rand(1, 5))->format('Y-m-d'),
            'expiry_date_gregorian' => Carbon::now()->addYears(rand(1, 5))->toDateString(),
            'tax_authority_number' => strtoupper($this->faker->bothify('TAX-#####')),
            'remaining_days' => $this->faker->numberBetween(10, 365),
            'vat' => $this->faker->randomFloat(2, 5, 15),
            'parent_company_id' => null, // Parent will be assigned in the seeder
            'insurance_company_id' => null, // Assigned dynamically
        ];
    }
}
