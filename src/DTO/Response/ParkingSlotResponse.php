<?php

namespace App\DTO\Response;

use App\Entity\ParkingSlot;
use App\Enum\SlotType;

final readonly class ParkingSlotResponse
{
    public function __construct(
        public string $id,
        public string $lotId,
        public string $slotNumber,
        public SlotType $slotType,
        public float $pricePerHour,
        public bool $isActive,
    ) {
    }

    public static function fromEntity(ParkingSlot $slot): self
    {
        return new self(
            (string) $slot->getId(),
            (string) $slot->getLot()->getId(),
            $slot->getSlotNumber(),
            $slot->getSlotType(),
            $slot->getPricePerHour(),
            $slot->isActive(),
        );
    }
}
