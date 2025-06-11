<?php

namespace App\Services;

use Carbon\Carbon;
use DateTime;

class CodeDecoder
{
    private int $A   = 17;
    private int $B   = 73;
    private int $MOD = 1000;

    private function modInverse(int $a, int $m): int|null
    {
        for ($x = 1; $x < $m; $x++) {
            if (($a * $x) % $m === 1) return $x;
        }
        return null;
    }

    /**
     * @throws \RuntimeException
     */
    public function decode(string $code, int $employeeId, Carbon $now = null): int
    {
        $now        = $now ?: Carbon::now('Asia/Riyadh');
        $hour       = (int) $now->format('G');                 // 0-23
        $check      = (int) substr($code, 0, 2);               // أول خانتين
        $siteCode   = (int) substr($code, 2, 3);               // الثلاث التالية

        $Ainv = $this->modInverse($this->A, $this->MOD);
        if ($Ainv === null) {
            throw new \RuntimeException('لا يمكن حساب المعكوس الضربي لـ A');
        }

        $siteId = ($Ainv * (($siteCode - $this->B + $this->MOD) % $this->MOD)) % $this->MOD;

        $daySeed       = (int) $now->format('Ymd');
        $expectedCheck = ($siteId + ($employeeId % 100) + $hour * 37 + $daySeed) % 100;

        if ($check !== $expectedCheck) {
            throw new \RuntimeException('الكود غير صالح');
        }

        return $siteId;   // zone_id المستنتج
    }
}
