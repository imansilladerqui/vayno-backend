<?php

namespace App\Enum;

enum CheckEventType: string
{
    case CheckIn = 'CHECK_IN';
    case CheckOut = 'CHECK_OUT';
}
