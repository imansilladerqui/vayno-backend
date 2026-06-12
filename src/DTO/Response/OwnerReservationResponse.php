<?php

namespace App\DTO\Response;

use App\Entity\Reservation;
use App\Enum\ReservationStatus;

final readonly class OwnerReservationResponse
{
    public function __construct(
        public string $id,
        public string $slotId,
        public string $lotId,
        public string $lotName,
        public string $slotNumber,
        public string $renterId,
        public string $renterName,
        public string $renterEmail,
        public \DateTimeImmutable $startDt,
        public \DateTimeImmutable $endDt,
        public ReservationStatus $status,
        public ?float $totalPrice,
        public string $qrCode,
    ) {
    }

    public static function fromEntity(Reservation $reservation): self
    {
        $slot = $reservation->getSlot();
        $lot = $slot->getLot();
        $renter = $reservation->getRenter();

        return new self(
            (string) $reservation->getId(),
            (string) $slot->getId(),
            (string) $lot->getId(),
            $lot->getName(),
            $slot->getSlotNumber(),
            (string) $renter->getId(),
            $renter->getFullName(),
            $renter->getEmail(),
            $reservation->getStartDt(),
            $reservation->getEndDt(),
            $reservation->getStatus(),
            $reservation->getTotalPrice(),
            $reservation->getQrCode(),
        );
    }
}
