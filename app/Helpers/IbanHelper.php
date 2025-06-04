<?php

namespace App\Helpers;

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
}
