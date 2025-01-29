<?php
        namespace Database\Seeders;   
           use Illuminate\Database\Seeder;
            use App\Models\Policy;
            
class PolicySeeder extends Seeder
{
                public function run()
                {
                    // سياسة الإجازات السنوية
                    Policy::create([
                        'policy_name' => 'Annual Leave Policy',
                        'policy_type' => 'leave',
                        'description' => 'Employees can take up to 30 days annual leave if they have sufficient balance.',
                        'conditions' => json_encode([
                            'max_duration' => 30, // الحد الأقصى للإجازة
                            'min_balance' => 1, // الحد الأدنى لرصيد الإجازات
                        ]),
                        'request_type_id' => 1, // معرف النوع "leave"
                    ]);
            
                    // سياسة السلف المالية
                    Policy::create([
                        'policy_name' => 'Loan Policy',
                        'policy_type' => 'loan',
                        'description' => 'Employees can request a loan up to a maximum of 5000 SAR.',
                        'conditions' => json_encode([
                            'max_amount' => 5000, // الحد الأقصى للسلفة
                            'min_tenure' => 6, // الحد الأدنى لمدة الخدمة بالأشهر
                        ]),
                        'request_type_id' => 4, // معرف النوع "loan"
                    ]);
            
                    // سياسة التعويض
                    Policy::create([
                        'policy_name' => 'Compensation Policy',
                        'policy_type' => 'compensation',
                        'description' => 'Compensation requests are approved only if the employee provides valid documentation.',
                        'conditions' => json_encode([
                            'requires_documentation' => true, // ضرورة وجود وثائق داعمة
                        ]),
                        'request_type_id' => 3, // معرف النوع "compensation"
                    ]);
            
                    // سياسة الساعات الإضافية
                    Policy::create([
                        'policy_name' => 'Overtime Policy',
                        'policy_type' => 'overtime',
                        'description' => 'Overtime is compensated with additional pay based on company rules.',
                        'conditions' => json_encode([
                            'requires_approval' => true, // ضرورة الحصول على موافقة
                            'max_hours' => 20, // الحد الأقصى للساعات الإضافية شهريًا
                        ]),
                        'request_type_id' => 5, // معرف النوع "overtime"
                    ]);
            
                    // سياسة النقل
                    Policy::create([
                        'policy_name' => 'Transfer Policy',
                        'policy_type' => 'transfer',
                        'description' => 'Transfers require approval from both current and future managers.',
                        'conditions' => json_encode([
                            'requires_current_manager_approval' => true, // موافقة المدير الحالي
                            'requires_future_manager_approval' => true, // موافقة المدير المستقبلي
                        ]),
                        'request_type_id' => 2, // معرف النوع "transfer"
                    ]);
                }
            }
            