<?php

namespace App\MessageHandler;

use App\Message\ExpireReservationsMessage;
use App\Service\ReservationExpiryService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ExpireReservationsMessageHandler
{
    public function __construct(private readonly ReservationExpiryService $expiryService)
    {
    }

    public function __invoke(ExpireReservationsMessage $message): void
    {
        $this->expiryService->expireStaleReservations();
    }
}
