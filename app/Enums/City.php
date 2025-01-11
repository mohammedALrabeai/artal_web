<?php

namespace App\Enums;

enum City: string
{
    case Riyadh = 'Riyadh';
    case Jeddah = 'Jeddah';
    case Dammam = 'Dammam';
    case Mecca = 'Mecca';
    case Medina = 'Medina';
    case Abha = 'Abha';
    case Taif = 'Taif';
    case Khobar = 'Khobar';
    case Jubail = 'Jubail';
    case Tabuk = 'Tabuk';

    public function label(): string
    {
        return match ($this) {
            self::Riyadh => __('Riyadh'),
            self::Jeddah => __('Jeddah'),
            self::Dammam => __('Dammam'),
            self::Mecca => __('Mecca'),
            self::Medina => __('Medina'),
            self::Abha => __('Abha'),
            self::Taif => __('Taif'),
            self::Khobar => __('Khobar'),
            self::Jubail => __('Jubail'),
            self::Tabuk => __('Tabuk'),
        };
    }
}
