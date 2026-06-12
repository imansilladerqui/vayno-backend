<?php

namespace App\DTO\Response;

final readonly class ReceiptResponse
{
    public function __construct(
        public string $reservationId,
        public string $slotId,
        public \DateTimeImmutable $startDt,
        public \DateTimeImmutable $endDt,
        public \DateTimeImmutable $checkInAt,
        public \DateTimeImmutable $checkOutAt,
        public float $durationHours,
        public float $totalPrice,
    ) {
    }
}
