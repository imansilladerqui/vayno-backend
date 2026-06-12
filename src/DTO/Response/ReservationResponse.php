<?php

namespace App\DTO\Response;

use App\Entity\Reservation;
use App\Enum\ReservationStatus;

final readonly class ReservationResponse
{
    public function __construct(
        public string $id,
        public string $slotId,
        public string $renterId,
        public \DateTimeImmutable $startDt,
        public \DateTimeImmutable $endDt,
        public ReservationStatus $status,
        public ?float $totalPrice,
        public string $qrCode,
    ) {
    }

    public static function fromEntity(Reservation $reservation): self
    {
        return new self(
            (string) $reservation->getId(),
            (string) $reservation->getSlot()->getId(),
            (string) $reservation->getRenter()->getId(),
            $reservation->getStartDt(),
            $reservation->getEndDt(),
            $reservation->getStatus(),
            $reservation->getTotalPrice(),
            $reservation->getQrCode(),
        );
    }
}
