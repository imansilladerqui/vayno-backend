<?php

namespace App\DTO\Response;

use App\Entity\ParkingSlot;
use App\Enum\SlotType;

final readonly class AvailableSlotResponse
{
    public function __construct(
        public string $slotId,
        public string $lotId,
        public string $lotName,
        public string $address,
        public float $lat,
        public float $lng,
        public string $slotNumber,
        public SlotType $slotType,
        public float $pricePerHour,
    ) {
    }

    public static function fromSlot(ParkingSlot $slot): self
    {
        $lot = $slot->getLot();

        return new self(
            (string) $slot->getId(),
            (string) $lot->getId(),
            $lot->getName(),
            $lot->getAddress(),
            $lot->getLat(),
            $lot->getLng(),
            $slot->getSlotNumber(),
            $slot->getSlotType(),
            $slot->getPricePerHour(),
        );
    }
}
