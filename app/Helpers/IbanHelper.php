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
            '10' => Bank::SNB->value,             // البنك الأهلي السعودي
            '15' => Bank::AlBilad->value,         // بنك البلاد
            '20' => Bank::ANB->value,             // البنك العربي الوطني
            '30', '45' => Bank::SAB->value,       // البنك السعودي البريطاني (ساب)
            '40' => Bank::BSF->value,             // البنك السعودي الفرنسي
            '50' => Bank::RiyadBank->value,       // بنك الرياض
            '60', '05' => Bank::Alinma->value,    // مصرف الإنماء
            '65' => Bank::Mashreq->value,         // بنك المشرق
            '70' => Bank::AlJazira->value,        // بنك الجزيرة
            '80' => Bank::AlRajhi->value,         // مصرف الراجحي
            '85' => Bank::EmiratesNBD->value,     // بنك الإمارات دبي الوطني
            '86' => Bank::JPMorgan->value,        // جي بي مورغان تشيس
            '87' => Bank::ICBC->value,            // البنك الصناعي والتجاري الصيني
            '83' => Bank::SBI->value,             // بنك الدولة الهندي
            '90' => Bank::Meem->value,            // بنك الخليج الدولي (ميم)
            '77' => Bank::STC->value,             // بنك STC
            '73' => Bank::D360->value,            // بنك D360
            '88' => Bank::BNPParibas->value,      // بنك BNP باريبا
            '89' => Bank::DeutscheBank->value,    // دويتشه بنك
            '91' => Bank::BankMuscat->value,      // بنك مسقط
            '92' => Bank::FAB->value,             // بنك أبوظبي الأول
            '93' => Bank::NBK->value,             // بنك الكويت الوطني
            '94' => Bank::NBB->value,             // بنك البحرين الوطني
            '95' => Bank::NBP->value,             // بنك باكستان الوطني
            '96' => Bank::QNB->value,             // بنك قطر الوطني
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
