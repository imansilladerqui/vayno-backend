<?php

namespace App\Security;

use App\Entity\User;
use App\Exception\ApiException;
use Symfony\Component\HttpFoundation\Response;

final class RoleChecker
{
    public static function requireOwner(User $user): void
    {
        if (!$user->isOwner()) {
            throw new ApiException('Owner role required', Response::HTTP_FORBIDDEN);
        }
    }

    public static function requireRenter(User $user): void
    {
        if (!$user->isRenter()) {
            throw new ApiException('Renter role required', Response::HTTP_FORBIDDEN);
        }
    }
}
