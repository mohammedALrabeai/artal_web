<?php

namespace App\Enums;

enum BloodType: string
{
    case APlus = 'A+';
    case AMinus = 'A-';
    case BPlus = 'B+';
    case BMinus = 'B-';
    case OPlus = 'O+';
    case OMinus = 'O-';
    case ABPlus = 'AB+';
    case ABMinus = 'AB-';

    public function label(): string
    {
        return match ($this) {
            self::APlus => __('A+'),
            self::AMinus => __('A-'),
            self::BPlus => __('B+'),
            self::BMinus => __('B-'),
            self::OPlus => __('O+'),
            self::OMinus => __('O-'),
            self::ABPlus => __('AB+'),
            self::ABMinus => __('AB-'),
        };
    }
}
