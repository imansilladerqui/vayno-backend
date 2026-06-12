<?php

namespace App\Enum;

enum SlotType: string
{
    case Standard = 'STANDARD';
    case Ev = 'EV';
    case Handicap = 'HANDICAP';
    case Motorcycle = 'MOTORCYCLE';
}
