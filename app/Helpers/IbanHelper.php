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

        $banks = [
            '10' => Bank::SNB->value,
            '20' => Bank::ANB->value,
            '30' => Bank::SABB->value,
            '40' => Bank::BSF->value,
            '50' => Bank::RiyadBank->value,
            '60' => Bank::AlInma->value,
            '70' => Bank::AlJazira->value,
            '75' => Bank::GIB->value,
            '80' => Bank::AlRajhi->value,
            '85' => Bank::NBD->value,
            '90' => Bank::AlBilad->value,
            '95' => Bank::Meem->value,
            '65' => Bank::Mashreq->value,
        ];

        return $banks[$bankCode] ?? null;
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
