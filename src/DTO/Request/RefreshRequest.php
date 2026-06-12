<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class RefreshRequest
{
    #[Assert\NotBlank]
    public string $refreshToken = '';
}
