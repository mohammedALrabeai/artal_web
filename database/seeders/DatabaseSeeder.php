<?php

namespace Database\Seeders;


// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Policy;
use App\Models\RequestType;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        if (RequestType::count() === 0) {
       // إدخال الأنواع
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

    // جلب المعرفات الفعلية للأنواع
    $requestTypeIds = RequestType::pluck('id', 'key');

    // إدخال السياسات
    $policies = [
        [
            'policy_name' => 'Annual Leave Policy',
            'policy_type' => 'leave',
            'description' => 'Employees can take up to 30 days annual leave if they have sufficient balance.',
            'conditions' => json_encode([
                'max_duration' => 30,
                'min_balance' => 1,
            ]),
            'request_type_id' => $requestTypeIds['leave'] ?? null,
        ],
        [
            'policy_name' => 'Loan Policy',
            'policy_type' => 'loan',
            'description' => 'Employees can request a loan up to a maximum of 5000 SAR.',
            'conditions' => json_encode([
                'max_amount' => 5000,
                'min_tenure' => 6,
            ]),
            'request_type_id' => $requestTypeIds['loan'] ?? null,
        ],
        [
            'policy_name' => 'Compensation Policy',
            'policy_type' => 'compensation',
            'description' => 'Compensation requests are approved only if the employee provides valid documentation.',
            'conditions' => json_encode([
                'requires_documentation' => true,
            ]),
            'request_type_id' => $requestTypeIds['compensation'] ?? null,
        ],
        [
            'policy_name' => 'Overtime Policy',
            'policy_type' => 'overtime',
            'description' => 'Overtime is compensated with additional pay based on company rules.',
            'conditions' => json_encode([
                'requires_approval' => true,
                'max_hours' => 20,
            ]),
            'request_type_id' => $requestTypeIds['overtime'] ?? null,
        ],
        [
            'policy_name' => 'Transfer Policy',
            'policy_type' => 'transfer',
            'description' => 'Transfers require approval from both current and future managers.',
            'conditions' => json_encode([
                'requires_current_manager_approval' => true,
                'requires_future_manager_approval' => true,
            ]),
            'request_type_id' => $requestTypeIds['transfer'] ?? null,
        ],
    ];

    foreach ($policies as $policy) {
        if ($policy['request_type_id']) {
            Policy::updateOrCreate(
                ['policy_name' => $policy['policy_name']],
                $policy
            );
        }
    }

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    $this->call([
        RequestTypeSeeder::class,
        PolicySeeder::class,
        ApprovalFlowSeeder::class,
    ]);
}
}
