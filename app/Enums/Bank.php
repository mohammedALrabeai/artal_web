<?php
namespace App\Enums;

enum Bank: string
{
    case AlRajhi = 'AlRajhi';
    case RiyadBank = 'RiyadBank';
    case NCB = 'NCB';

    public function label(): string
    {
        return match ($this) {
            self::AlRajhi => __('AlRajhi'),
            self::RiyadBank => __('Riyad Bank'),
            self::NCB => __('National Commercial Bank'),
        };
    }
}
