<?php

namespace App\Service;

use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ReservationExpiryService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ReservationRepository $reservationRepository,
        private readonly int $graceMinutes,
    ) {
    }

    public function expireStaleReservations(): int
    {
        $cutoff = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->modify(sprintf('-%d minutes', $this->graceMinutes));

        $reservations = $this->reservationRepository->createQueryBuilder('r')
            ->where('r.status IN (:statuses)')
            ->andWhere('r.startDt < :cutoff')
            ->setParameter('statuses', [ReservationStatus::Pending, ReservationStatus::Confirmed])
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($reservations as $reservation) {
            $reservation->setStatus(ReservationStatus::Expired);
            ++$count;
        }

        if ($count > 0) {
            $this->em->flush();
        }

        return $count;
    }
}
