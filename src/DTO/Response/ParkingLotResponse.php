<?php

namespace App\DTO\Response;

use App\Entity\ParkingLot;

final readonly class ParkingLotResponse
{
    public function __construct(
        public string $id,
        public string $ownerId,
        public string $name,
        public string $address,
        public float $lat,
        public float $lng,
        public bool $isActive,
    ) {
    }

    public static function fromEntity(ParkingLot $lot): self
    {
        return new self(
            (string) $lot->getId(),
            (string) $lot->getOwner()->getId(),
            $lot->getName(),
            $lot->getAddress(),
            $lot->getLat(),
            $lot->getLng(),
            $lot->isActive(),
        );
    }
}
