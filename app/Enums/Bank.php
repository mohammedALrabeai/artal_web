<?php

namespace App\Enums;

enum Bank: string
{
    case AlRajhi   = 'AlRajhi';
    case SNB       = 'SNB';
    case ANB       = 'ANB';
    case SABB      = 'SABB';
    case BSF       = 'BSF';
    case RiyadBank = 'RiyadBank';
    case AlInma    = 'AlInma';
    case AlJazira  = 'AlJazira';
    case GIB       = 'GIB';
    case AlBilad   = 'AlBilad';
    case NBD       = 'NBD';
    case Meem      = 'Meem';
    case Mashreq   = 'Mashreq';

    public function label(): string
    {
        return __("bank.{$this->value}");
    }
}
