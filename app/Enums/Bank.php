<?php

namespace App\Enums;

enum Bank: string
{
    case AlRajhi = 'AlRajhi';
    case NCB = 'NCB';
    case SABB = 'SABB';
    case BSFR = 'BSFR';
    case ARNB = 'ARNB';
    case RiyadBank = 'RiyadBank';
    case AlBilad = 'AlBilad';
    case AlInma = 'AlInma';
    case AlJazira = 'AlJazira';
    case SABB2 = 'SABB2';

    public function label(): string
    {
        return match ($this) {
            self::AlRajhi => __('AlRajhi'),
            self::NCB => __('National Commercial Bank'),
            self::SABB => __('SABB'),
            self::BSFR => __('Saudi French Bank'),
            self::ARNB => __('Arab National Bank'),
            self::RiyadBank => __('Riyad Bank'),
            self::AlBilad => __('AlBilad Bank'),
            self::AlInma => __('AlInma Bank'),
            self::AlJazira => __('Bank AlJazira'),
            self::SABB2 => __('Saudi British Bank'),
        };
    }
}
