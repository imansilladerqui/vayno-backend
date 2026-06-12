<?php

namespace App\DTO\Response;

use App\Entity\SlotAvailability;

final readonly class SlotAvailabilityResponse
{
    public function __construct(
        public string $id,
        public string $slotId,
        public ?string $date,
        public string $startTime,
        public string $endTime,
        public bool $isRecurring,
        public ?int $weekday,
    ) {
    }

    public static function fromEntity(SlotAvailability $window): self
    {
        return new self(
            (string) $window->getId(),
            (string) $window->getSlot()->getId(),
            $window->getDate()?->format('Y-m-d'),
            $window->getStartTime()->format('H:i:s'),
            $window->getEndTime()->format('H:i:s'),
            $window->isRecurring(),
            $window->getWeekday(),
        );
    }
}
