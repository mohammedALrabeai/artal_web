<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApprovalFlow;

class ApprovalFlowSeeder extends Seeder
{
    public function run()
    {
        // موافقات طلب الإجازة (leave)
        ApprovalFlow::create([
            'request_type' => 'leave',
            'approval_level' => 1,
            'approver_role' => 'hr', // الموارد البشرية
            'conditions' => json_encode([]), // لا شروط إضافية
        ]);

        ApprovalFlow::create([
            'request_type' => 'leave',
            'approval_level' => 2,
            'approver_role' => 'manager', // المدير المباشر
            'conditions' => json_encode([]), // لا شروط إضافية
        ]);

        ApprovalFlow::create([
            'request_type' => 'leave',
            'approval_level' => 3,
            'approver_role' => 'general_manager', // المدير العام
            'conditions' => json_encode([]), // لا شروط إضافية
        ]);

        // موافقات طلب السلفة (loan)
        ApprovalFlow::create([
            'request_type' => 'loan',
            'approval_level' => 1,
            'approver_role' => 'hr', // الموارد البشرية
            'conditions' => json_encode(['max_amount' => 5000]), // الحد الأقصى للمبلغ
        ]);

        ApprovalFlow::create([
            'request_type' => 'loan',
            'approval_level' => 2,
            'approver_role' => 'manager', // المدير المباشر
            'conditions' => json_encode([]), // لا شروط إضافية
        ]);

        ApprovalFlow::create([
            'request_type' => 'loan',
            'approval_level' => 3,
            'approver_role' => 'general_manager', // المدير العام
            'conditions' => json_encode([]), // لا شروط إضافية
        ]);

        // موافقات طلب النقل (transfer)
        ApprovalFlow::create([
            'request_type' => 'transfer',
            'approval_level' => 1,
            'approver_role' => 'hr', // الموارد البشرية
            'conditions' => json_encode([]), // لا شروط إضافية
        ]);

        ApprovalFlow::create([
            'request_type' => 'transfer',
            'approval_level' => 2,
            'approver_role' => 'manager', // المدير المباشر
            'conditions' => json_encode([]), // لا شروط إضافية
        ]);

        ApprovalFlow::create([
            'request_type' => 'transfer',
            'approval_level' => 3,
            'approver_role' => 'general_manager', // المدير العام
            'conditions' => json_encode([]), // لا شروط إضافية
        ]);
    }
}
