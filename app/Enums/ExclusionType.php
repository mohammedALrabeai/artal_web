<?php

namespace App\Enums;

enum ExclusionType: string
{
    case Resignation = 'Resignation';
    case Termination = 'Termination';
    case Retirement = 'Retirement';

    public function label(): string
    {
        return match ($this) {
            self::Resignation => __('Resignation'),
            self::Termination => __('Termination'),
            self::Retirement => __('Retirement'),
        };
    }
}
