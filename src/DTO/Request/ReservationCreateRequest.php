<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Assert\Callback('validateTimes')]
final class ReservationCreateRequest
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $slotId = '';

    #[Assert\NotNull]
    public ?\DateTimeImmutable $startDt = null;

    #[Assert\NotNull]
    public ?\DateTimeImmutable $endDt = null;

    public function validateTimes(ExecutionContextInterface $context): void
    {
        if ($this->startDt && $this->endDt && $this->startDt >= $this->endDt) {
            $context->buildViolation('start_dt must be before end_dt')->addViolation();
        }
    }
}
