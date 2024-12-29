<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\RequestType;

class RequestTypeSeeder extends Seeder
{
    public function run()
    {
        RequestType::create(['key' => 'leave', 'name' => 'Leave Request']);
        RequestType::create(['key' => 'transfer', 'name' => 'Transfer Request']);
        RequestType::create(['key' => 'compensation', 'name' => 'Compensation Request']);
        RequestType::create(['key' => 'loan', 'name' => 'Loan Request']);
        RequestType::create(['key' => 'overtime', 'name' => 'Overtime Request']);
    }
}
