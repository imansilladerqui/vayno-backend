<?php

namespace App\DTO\Request;

use App\Enum\UserRole;
use Symfony\Component\Validator\Constraints as Assert;

final class UserUpdateRequest
{
    #[Assert\Length(min: 1, max: 255)]
    public ?string $fullName = null;

    public ?UserRole $role = null;
}
