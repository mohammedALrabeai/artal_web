<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RequestType;

class RequestTypeSeeder extends Seeder
{
    public function run()
    {
        $requestTypes = [
            ['key' => 'leave', 'name' => 'Leave Request'],
            ['key' => 'transfer', 'name' => 'Transfer Request'],
            ['key' => 'compensation', 'name' => 'Compensation Request'],
            ['key' => 'loan', 'name' => 'Loan Request'],
            ['key' => 'overtime', 'name' => 'Overtime Request'],
        ];

        foreach ($requestTypes as $type) {
            RequestType::updateOrCreate(
                ['key' => $type['key']],
                ['name' => $type['name']]
            );
        }
    }
}
