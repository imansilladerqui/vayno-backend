<?php

namespace App\Service;

use App\DTO\Request\ReservationCreateRequest;
use App\Entity\Reservation;
use App\Entity\User;
use App\Enum\ReservationStatus;
use App\Repository\ParkingSlotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class ReservationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ParkingSlotRepository $slotRepository,
        private readonly SlotService $slotService,
    ) {
    }

    public function createReservation(User $renter, ReservationCreateRequest $data): Reservation
    {
        $slot = $this->slotRepository->createQueryBuilder('s')
            ->join('s.lot', 'l')
            ->addSelect('l')
            ->where('s.id = :id')
            ->setParameter('id', Uuid::fromString($data->slotId))
            ->getQuery()
            ->getOneOrNullResult();
        if (!$slot) {
            throw new \InvalidArgumentException('Slot not found');
        }

        if (!$this->slotService->slotIsAvailableForReservation($slot, $data->startDt, $data->endDt)) {
            throw new \InvalidArgumentException('Slot is not available for the requested time range');
        }

        $reservation = new Reservation();
        $reservation->setSlot($slot);
        $reservation->setRenter($renter);
        $reservation->setStartDt($data->startDt);
        $reservation->setEndDt($data->endDt);
        $reservation->setStatus(ReservationStatus::Confirmed);
        $reservation->setTotalPrice($this->estimatePrice($slot->getPricePerHour(), $data->startDt, $data->endDt));
        $reservation->setQrCode($this->generateQrCode());

        $this->em->persist($reservation);
        $this->em->flush();

        return $reservation;
    }

    public function cancelReservation(Reservation $reservation): Reservation
    {
        if (in_array($reservation->getStatus(), [
            ReservationStatus::Completed,
            ReservationStatus::Cancelled,
            ReservationStatus::Expired,
        ], true)) {
            throw new \InvalidArgumentException('Reservation cannot be cancelled');
        }

        $reservation->setStatus(ReservationStatus::Cancelled);
        $this->em->flush();

        return $reservation;
    }

    private function generateQrCode(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function estimatePrice(float $pricePerHour, \DateTimeImmutable $startDt, \DateTimeImmutable $endDt): float
    {
        $hours = ($endDt->getTimestamp() - $startDt->getTimestamp()) / 3600;

        return round(ceil($hours) * $pricePerHour, 2);
    }
}
