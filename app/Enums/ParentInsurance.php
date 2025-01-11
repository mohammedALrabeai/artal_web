<?php

namespace App\Enums;

enum ParentInsurance: string
{
    case NotIncluded = 'غير مشمول';
    case FamilyInsurance = 'يوجد تأمين أسره';
    case CooperativeFamily = 'العائلة تعاونية';
    case SpouseAndChildren = 'زوجه وأولاد';
    case CooperativeFamilyOnly = 'العائلة في التعاونية';
    case DoubleInsurance = 'تأمين مزودوج للعائلة';
    case WithoutInsurance = 'بدون تامين';
    case FatherAndMother = 'أب وأم';
    case FatherOnly = 'أب';
    case SpouseAndSon = 'زوجه وإبن';
    case SpouseAndDaughter = 'الزوجة والبنت';

    public function label(): string
    {
        return match ($this) {
            self::NotIncluded => __('غير مشمول'),
            self::FamilyInsurance => __('يوجد تأمين أسره'),
            self::CooperativeFamily => __('العائلة تعاونية'),
            self::SpouseAndChildren => __('زوجه وأولاد'),
            self::CooperativeFamilyOnly => __('العائلة في التعاونية'),
            self::DoubleInsurance => __('تأمين مزودوج للعائلة'),
            self::WithoutInsurance => __('بدون تامين'),
            self::FatherAndMother => __('أب وأم'),
            self::FatherOnly => __('أب'),
            self::SpouseAndSon => __('زوجه وإبن'),
            self::SpouseAndDaughter => __('الزوجة والبنت'),
        };
    }
}
