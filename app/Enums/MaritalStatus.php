<?php
namespace App\Enums;

enum MaritalStatus: string
{
    case SINGLE = 'single';
    case MARRIED = 'married';
    case DIVORCED = 'divorced';
    case WIDOWED = 'widowed';

    public function label(): string
    {
        return match($this) {
            self::SINGLE => __('marital_status.single'),
            self::MARRIED => __('marital_status.married'),
            self::DIVORCED => __('marital_status.divorced'),
            self::WIDOWED => __('marital_status.widowed'),
        };
    }
}
