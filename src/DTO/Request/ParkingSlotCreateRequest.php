<?php

namespace App\DTO\Request;

use App\Enum\SlotType;
use Symfony\Component\Validator\Constraints as Assert;

final class ParkingSlotCreateRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    public string $slotNumber = '';

    public SlotType $slotType = SlotType::Standard;

    #[Assert\Positive]
    public float $pricePerHour = 0.0;

    public bool $isActive = true;
}
