<?php

namespace App\Service;

use App\DTO\Response\ReceiptResponse;
use App\Entity\CheckEvent;
use App\Entity\Reservation;
use App\Enum\CheckEventType;
use App\Enum\ReservationStatus;
use App\Repository\CheckEventRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CheckInService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CheckEventRepository $checkEventRepository,
        private readonly int $graceMinutes,
    ) {
    }

    public function checkIn(Reservation $reservation, string $qrCode): Reservation
    {
        if ($reservation->getQrCode() !== $qrCode) {
            throw new \InvalidArgumentException('Invalid QR code');
        }
        if (!in_array($reservation->getStatus(), [ReservationStatus::Pending, ReservationStatus::Confirmed], true)) {
            throw new \InvalidArgumentException('Reservation is not eligible for check-in');
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if ($now > $reservation->getStartDt()) {
            $secondsLate = $now->getTimestamp() - $reservation->getStartDt()->getTimestamp();
            if ($secondsLate > $this->graceMinutes * 60) {
                $reservation->setStatus(ReservationStatus::Expired);
                $this->em->flush();
                throw new \InvalidArgumentException('Reservation has expired');
            }
        }

        $reservation->setStatus(ReservationStatus::Active);
        $event = new CheckEvent();
        $event->setReservation($reservation);
        $event->setEventType(CheckEventType::CheckIn);
        $event->setTimestamp($now);
        $this->em->persist($event);
        $this->em->flush();

        return $reservation;
    }

    public function checkOut(Reservation $reservation): ReceiptResponse
    {
        if ($reservation->getStatus() !== ReservationStatus::Active) {
            throw new \InvalidArgumentException('Reservation is not active');
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $checkOutEvent = new CheckEvent();
        $checkOutEvent->setReservation($reservation);
        $checkOutEvent->setEventType(CheckEventType::CheckOut);
        $checkOutEvent->setTimestamp($now);
        $this->em->persist($checkOutEvent);

        $checkInEvent = $this->checkEventRepository->findOneBy([
            'reservation' => $reservation,
            'eventType' => CheckEventType::CheckIn,
        ]);
        if (!$checkInEvent) {
            throw new \RuntimeException('Check-in event not found');
        }

        $durationHours = max(($now->getTimestamp() - $checkInEvent->getTimestamp()->getTimestamp()) / 3600, 0);
        $billedHours = $durationHours > 0 ? ceil($durationHours) : 1;
        $totalPrice = round($billedHours * $reservation->getSlot()->getPricePerHour(), 2);

        $reservation->setStatus(ReservationStatus::Completed);
        $reservation->setTotalPrice($totalPrice);
        $this->em->flush();

        return new ReceiptResponse(
            (string) $reservation->getId(),
            (string) $reservation->getSlot()->getId(),
            $reservation->getStartDt(),
            $reservation->getEndDt(),
            $checkInEvent->getTimestamp(),
            $now,
            round($durationHours, 2),
            $totalPrice,
        );
    }
}
