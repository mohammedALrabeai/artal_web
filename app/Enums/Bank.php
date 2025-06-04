<?php

namespace App\Enums;

enum Bank: string
{
    case SNB = 'SNB';
    case AlBilad = 'AlBilad';
    case ANB = 'ANB';
    case SAB = 'SAB';
    case BSF = 'BSF';
    case RiyadBank = 'RiyadBank';
    case Alinma = 'Alinma';
    case Mashreq = 'Mashreq';
    case AlJazira = 'AlJazira';
    case AlRajhi = 'AlRajhi';
    case EmiratesNBD = 'EmiratesNBD';
    case JPMorgan = 'JPMorgan';
    case ICBC = 'ICBC';
    case SBI = 'SBI';
    case Meem = 'Meem';
    case STC = 'STC';
    case D360 = 'D360';
    case BNPParibas = 'BNPParibas';
    case DeutscheBank = 'DeutscheBank';
    case BankMuscat = 'BankMuscat';
    case FAB = 'FAB';
    case NBK = 'NBK';
    case NBB = 'NBB';
    case NBP = 'NBP';
    case QNB = 'QNB';

    public function label(): string
    {
        return __("bank.{$this->value}");
    }
}
