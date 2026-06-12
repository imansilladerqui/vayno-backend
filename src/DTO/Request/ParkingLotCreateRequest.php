<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ParkingLotCreateRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 500)]
    public string $address = '';

    #[Assert\NotNull]
    public float $lat = 0.0;

    #[Assert\NotNull]
    public float $lng = 0.0;

    public bool $isActive = true;
}
