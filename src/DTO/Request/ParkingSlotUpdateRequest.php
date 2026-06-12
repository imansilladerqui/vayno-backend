<?php

namespace App\DTO\Request;

use App\Enum\SlotType;
use Symfony\Component\Validator\Constraints as Assert;

final class ParkingSlotUpdateRequest
{
    #[Assert\Length(min: 1, max: 50)]
    public ?string $slotNumber = null;

    public ?SlotType $slotType = null;

    #[Assert\Positive]
    public ?float $pricePerHour = null;

    public ?bool $isActive = null;
}
