<?php

namespace App\Service;

use App\DTO\Request\SlotAvailabilityCreateRequest;
use App\DTO\Response\AvailableSlotResponse;
use App\Entity\ParkingSlot;
use App\Entity\SlotAvailability;
use App\Enum\ReservationStatus;
use App\Repository\ParkingSlotRepository;
use App\Repository\ReservationRepository;
use App\Repository\SlotAvailabilityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class SlotService
{
    /** @var list<ReservationStatus> */
    private const ACTIVE_RESERVATION_STATUSES = [
        ReservationStatus::Pending,
        ReservationStatus::Confirmed,
        ReservationStatus::Active,
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ParkingSlotRepository $slotRepository,
        private readonly SlotAvailabilityRepository $availabilityRepository,
        private readonly ReservationRepository $reservationRepository,
    ) {
    }

    public function getSlotForOwner(Uuid $slotId, Uuid $ownerId): ?ParkingSlot
    {
        return $this->slotRepository->createQueryBuilder('s')
            ->join('s.lot', 'l')
            ->where('s.id = :slotId')
            ->andWhere('l.owner = :ownerId')
            ->setParameter('slotId', $slotId)
            ->setParameter('ownerId', $ownerId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function validateAvailabilityOverlap(Uuid $slotId, SlotAvailabilityCreateRequest $data): void
    {
        $existing = $this->availabilityRepository->findBy(['slot' => $slotId]);

        foreach ($existing as $window) {
            if ($data->isRecurring && $window->isRecurring()) {
                if ($data->weekday === $window->getWeekday() && $this->timesOverlap($data->startTime, $data->endTime, $window)) {
                    throw new \InvalidArgumentException('Availability window overlaps with existing recurring window');
                }
            } elseif (!$data->isRecurring && !$window->isRecurring()) {
                if ($data->date === $window->getDate()?->format('Y-m-d')
                    && $this->timesOverlap($data->startTime, $data->endTime, $window)) {
                    throw new \InvalidArgumentException('Availability window overlaps with existing window');
                }
            }
        }
    }

    /**
     * @return list<AvailableSlotResponse>
     */
    public function findAvailableSlots(
        \DateTimeImmutable $targetDate,
        \DateTimeImmutable $startDt,
        \DateTimeImmutable $endDt,
        ?float $lat = null,
        ?float $lng = null,
        float $radiusKm = 10.0,
    ): array {
        $slots = $this->slotRepository->createQueryBuilder('s')
            ->join('s.lot', 'l')
            ->addSelect('l')
            ->leftJoin('s.availabilityWindows', 'w')
            ->addSelect('w')
            ->where('s.isActive = true')
            ->andWhere('l.isActive = true')
            ->getQuery()
            ->getResult();

        $available = [];
        $startT = $startDt->format('H:i:s');
        $endT = $endDt->format('H:i:s');

        foreach ($slots as $slot) {
            $lot = $slot->getLot();
            if ($lat !== null && $lng !== null) {
                $delta = $radiusKm / 111.0;
                if (abs($lot->getLat() - $lat) > $delta || abs($lot->getLng() - $lng) > $delta) {
                    continue;
                }
            }

            $covers = false;
            foreach ($slot->getAvailabilityWindows() as $window) {
                if ($this->slotCoversRequestedWindow($window, $targetDate, $startT, $endT)) {
                    $covers = true;
                    break;
                }
            }
            if (!$covers) {
                continue;
            }

            if ($this->hasReservationConflict($slot->getId(), $startDt, $endDt)) {
                continue;
            }

            $available[] = AvailableSlotResponse::fromSlot($slot);
        }

        return $available;
    }

    public function slotIsAvailableForReservation(ParkingSlot $slot, \DateTimeImmutable $startDt, \DateTimeImmutable $endDt): bool
    {
        if (!$slot->isActive() || !$slot->getLot()->isActive()) {
            return false;
        }

        $targetDate = $startDt;
        $startT = $startDt->format('H:i:s');
        $endT = $endDt->format('H:i:s');

        $windows = $this->availabilityRepository->findBy(['slot' => $slot]);
        $covers = false;
        foreach ($windows as $window) {
            if ($this->slotCoversRequestedWindow($window, $targetDate, $startT, $endT)) {
                $covers = true;
                break;
            }
        }
        if (!$covers) {
            return false;
        }

        return !$this->hasReservationConflict($slot->getId(), $startDt, $endDt);
    }

    public function parseTime(string $time): \DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat('H:i:s', $time)
            ?: \DateTimeImmutable::createFromFormat('H:i', $time);
        if (!$parsed) {
            throw new \InvalidArgumentException('Invalid time format');
        }

        return $parsed;
    }

    public function createAvailabilityEntity(ParkingSlot $slot, SlotAvailabilityCreateRequest $data): SlotAvailability
    {
        $window = new SlotAvailability();
        $window->setSlot($slot);
        $window->setStartTime($this->parseTime($data->startTime));
        $window->setEndTime($this->parseTime($data->endTime));
        $window->setIsRecurring($data->isRecurring);
        if ($data->isRecurring) {
            $window->setWeekday($data->weekday);
            $window->setDate(null);
        } else {
            $window->setDate(new \DateTimeImmutable($data->date));
            $window->setWeekday(null);
        }

        return $window;
    }

    private function hasReservationConflict(Uuid $slotId, \DateTimeImmutable $startDt, \DateTimeImmutable $endDt): bool
    {
        return $this->reservationRepository->createQueryBuilder('r')
            ->where('r.slot = :slotId')
            ->andWhere('r.status IN (:statuses)')
            ->andWhere('r.startDt < :endDt')
            ->andWhere('r.endDt > :startDt')
            ->setParameter('slotId', $slotId)
            ->setParameter('statuses', self::ACTIVE_RESERVATION_STATUSES)
            ->setParameter('startDt', $startDt)
            ->setParameter('endDt', $endDt)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult() !== null;
    }

    private function slotCoversRequestedWindow(
        SlotAvailability $window,
        \DateTimeImmutable $targetDate,
        string $startT,
        string $endT,
    ): bool {
        $windowStart = $window->getStartTime()->format('H:i:s');
        $windowEnd = $window->getEndTime()->format('H:i:s');

        $weekday = (int) $targetDate->format('N') - 1; // Monday=0 (Python weekday convention)

        if ($window->isRecurring()) {
            if ($window->getWeekday() !== $weekday) {
                return false;
            }

            return $windowStart <= $startT && $windowEnd >= $endT;
        }

        if ($window->getDate()?->format('Y-m-d') !== $targetDate->format('Y-m-d')) {
            return false;
        }

        return $windowStart <= $startT && $windowEnd >= $endT;
    }

    private function timesOverlap(string $startA, string $endA, SlotAvailability $window): bool
    {
        $startB = $window->getStartTime()->format('H:i:s');
        $endB = $window->getEndTime()->format('H:i:s');

        return $startA < $endB && $startB < $endA;
    }
}
