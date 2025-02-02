<?php

namespace App\Enums;

enum CoverageReason: string
{
    case SHORTAGE = 'shortage';
    case ABSENT_EMPLOYEE = 'absent_employee';
    case ROTATION = 'rotation';
    case EMERGENCY = 'emergency';
    case TRAINING = 'training';


    /**
     * إرجاع قائمة الأسباب لاستخدامها في الفورم
     */
    public static function labels(): array
    {
        return [
            self::SHORTAGE->value => __('Shortage in Staff'),
            self::ABSENT_EMPLOYEE->value => __('Employee Absent'),
            self::ROTATION->value => __('Rotate Employee'),
            self::EMERGENCY->value => __('Emergency Leave'),
            self::TRAINING->value => __('Training or Course'),
        ];
    }

    /**
     * التحقق مما إذا كان السبب يتطلب اختيار موظف بديل
     */
    public function requiresReplacement(): bool
    {
        return match ($this) {
            self::ABSENT_EMPLOYEE,self::ROTATION, self::EMERGENCY => true,
            
            default => false,
        };
    }
}
