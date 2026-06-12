<?php

namespace App\Enum;

enum UserRole: string
{
    case Owner = 'OWNER';
    case Renter = 'RENTER';
    case Both = 'BOTH';
}
