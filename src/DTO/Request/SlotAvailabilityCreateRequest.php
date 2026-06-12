<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Assert\Callback('validateAvailability')]
final class SlotAvailabilityCreateRequest
{
    public ?string $date = null;

    #[Assert\NotBlank]
    public string $startTime = '';

    #[Assert\NotBlank]
    public string $endTime = '';

    public bool $isRecurring = false;

    #[Assert\Range(min: 0, max: 6)]
    public ?int $weekday = null;

    public function validateAvailability(ExecutionContextInterface $context): void
    {
        $start = \DateTimeImmutable::createFromFormat('H:i:s', $this->startTime)
            ?: \DateTimeImmutable::createFromFormat('H:i', $this->startTime);
        $end = \DateTimeImmutable::createFromFormat('H:i:s', $this->endTime)
            ?: \DateTimeImmutable::createFromFormat('H:i', $this->endTime);

        if (!$start || !$end || $start >= $end) {
            $context->buildViolation('start_time must be before end_time')->addViolation();
        }

        if ($this->isRecurring) {
            if ($this->weekday === null) {
                $context->buildViolation('weekday is required for recurring availability')->addViolation();
            }
        } elseif ($this->date === null) {
            $context->buildViolation('date is required for non-recurring availability')->addViolation();
        }
    }
}
