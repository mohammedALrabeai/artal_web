<?php

namespace App\Helpers;

use App\Enums\Bank;

class IbanHelper
{
    public static function detectBankFromIban(?string $iban): ?string
    {
        if (!$iban || strlen($iban) < 6) return null;

        $bankCode = substr(strtoupper(str_replace(' ', '', $iban)), 4, 2);

        $banks = [
            '80' => 'AlRajhi',
            '10' => 'NCB',
            '50' => 'Riyad Bank',
            '30' => 'SABB',
            '40' => 'BSF',
            '20' => 'ANB',
            '60' => 'AlInma',
            '70' => 'Bank AlJazira',
            '90' => 'Meem',
        ];

        return $banks[$bankCode] ?? null;
    }



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
