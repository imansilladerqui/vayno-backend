<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ParkingLotUpdateRequest
{
    #[Assert\Length(min: 1, max: 255)]
    public ?string $name = null;

    #[Assert\Length(min: 1, max: 500)]
    public ?string $address = null;

    public ?float $lat = null;

    public ?float $lng = null;

    public ?bool $isActive = null;
}
