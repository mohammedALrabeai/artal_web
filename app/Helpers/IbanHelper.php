<?php

namespace App\Helpers;

use App\Enums\Bank;

class IbanHelper
{
    /**
     * يكتشف اختصار البنك من رقم الآيبان (باستخدام كود البنك داخل الآيبان)
     */
    public static function detectBankFromIban(?string $iban): ?string
    {
        if (!$iban || strlen($iban) < 6) return null;

        $bankCode = substr(strtoupper(str_replace(' ', '', $iban)), 4, 2);

        return match ($bankCode) {
            '10' => \App\Enums\Bank::SNB->value,        // البنك الأهلي السعودي
            '20' => \App\Enums\Bank::ANB->value,        // البنك العربي الوطني
            '30', '45' => \App\Enums\Bank::SABB->value, // البنك السعودي البريطاني (ساب)
            '40' => \App\Enums\Bank::BSF->value,        // البنك السعودي الفرنسي
            '50' => \App\Enums\Bank::RiyadBank->value,  // بنك الرياض
            '60' => \App\Enums\Bank::Alinma->value,     // مصرف الإنماء
            '65' => \App\Enums\Bank::Mashreq->value,    // بنك المشرق
            '70' => \App\Enums\Bank::AlJazira->value,   // بنك الجزيرة
            '80' => \App\Enums\Bank::AlRajhi->value,    // مصرف الراجحي
            '85' => \App\Enums\Bank::NBD->value,        // بنك الإمارات دبي الوطني
            '90' => \App\Enums\Bank::Meem->value,       // بنك ميم (GIB)
            '15' => \App\Enums\Bank::AlBilad->value,    // بنك البلاد
            '83' => \App\Enums\Bank::SBI->value,        // بنك الدولة الهندي
            '86' => \App\Enums\Bank::JPM->value,        // جي بي مورغان تشيس
            '87' => \App\Enums\Bank::ICBC->value,       // البنك الصناعي والتجاري الصيني
            default => null,
        };
    }


    /**
     * يعيد التسمية الكاملة للبنك بناءً على اختصاره (حسب enum Bank)
     */
    public static function translateBankCode(?string $input): ?string
    {
        if (!$input) {
            return null;
        }

        foreach (Bank::cases() as $bank) {
            if (strtolower($input) === strtolower($bank->value)) {
                return $bank->label();
            }
        }

        return null;
    }
}
