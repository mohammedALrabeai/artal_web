<?php

namespace App\Enums;

enum JobTitle: string
{
    case OperationsManager = 'مدير العمليات';
    case LiaisonOfficer = 'معقب';
    case SecurityManager = 'مدير أمن';
    case SecuritySupervisor = 'مشرف أمن';
    case SecurityGuard = 'حارس أمن';
    case OperationsSupervisor = 'مشرف عمليات';

    public function label(): string
    {
        return match ($this) {
            self::OperationsManager => __('Operations Manager'),
            self::LiaisonOfficer => __('Liaison Officer'),
            self::SecurityManager => __('Security Manager'),
            self::SecuritySupervisor => __('Security Supervisor'),
            self::SecurityGuard => __('Security Guard'),
            self::OperationsSupervisor => __('Operations Supervisor'),
        };
    }
}
