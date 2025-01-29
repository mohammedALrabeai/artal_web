<?php

namespace App\Enums;

enum InsuranceType: string
{
    case NoInsurance = '';
    case SocialInsuranceSubscriber = 'commercial_record';

    /**
     * Get the label for the enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::NoInsurance => __('No Insurance'),
            self::SocialInsuranceSubscriber => __('Social Insurance Subscriber'),
        };
    }

    /**
     * Get the options for the select field.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->toArray();
    }
}
