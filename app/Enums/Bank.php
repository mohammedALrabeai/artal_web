<?php

namespace App\Enums;

enum Bank: string
{
    case SNB       = 'SNB';
    case ANB       = 'ANB';
    case SABB      = 'SABB';
    case BSF       = 'BSF';
    case RiyadBank = 'RiyadBank';
    case Alinma    = 'Alinma';
    case AlJazira  = 'AlJazira';
    case AlRajhi   = 'AlRajhi';
    case Meem      = 'Meem';
    case AlBilad   = 'AlBilad';
    case GIB       = 'GIB';
    case JPM       = 'JPM';
    case ICBC      = 'ICBC';
    case SBI       = 'SBI';
    case NBD       = 'NBD';
    case Mashreq   = 'Mashreq';

    public function label(): string
    {
        return __("bank.{$this->value}");
    }
}
