<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CheckInRequest
{
    #[Assert\NotBlank]
    public string $qrCode = '';
}
