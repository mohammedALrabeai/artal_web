<?php

namespace App\Enums;

enum ContractType: string
{
    case LIMITED = 'limited';
    case UNLIMITED = 'unlimited';

    public function label(): string
    {
        return match ($this) {
            self::LIMITED => __('Limited'),
            self::UNLIMITED => __('Unlimited'),
        };
    }

    public static function options(): array
    {
        return [
            self::LIMITED->value => self::LIMITED->label(),
            self::UNLIMITED->value => self::UNLIMITED->label(),
        ];
    }
}