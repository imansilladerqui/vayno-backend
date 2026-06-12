<?php

namespace App\Entity;

use App\Repository\SlotAvailabilityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SlotAvailabilityRepository::class)]
#[ORM\Table(name: 'slot_availability')]
class SlotAvailability
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: ParkingSlot::class, inversedBy: 'availabilityWindows')]
    #[ORM\JoinColumn(name: 'slot_id', nullable: false, onDelete: 'CASCADE')]
    private ParkingSlot $slot;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(name: 'start_time', type: 'time_immutable')]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(name: 'end_time', type: 'time_immutable')]
    private \DateTimeImmutable $endTime;

    #[ORM\Column(name: 'is_recurring', options: ['default' => false])]
    private bool $isRecurring = false;

    #[ORM\Column(nullable: true)]
    private ?int $weekday = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSlot(): ParkingSlot
    {
        return $this->slot;
    }

    public function setSlot(ParkingSlot $slot): self
    {
        $this->slot = $slot;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(?\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): \DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function isRecurring(): bool
    {
        return $this->isRecurring;
    }

    public function setIsRecurring(bool $isRecurring): self
    {
        $this->isRecurring = $isRecurring;

        return $this;
    }

    public function getWeekday(): ?int
    {
        return $this->weekday;
    }

    public function setWeekday(?int $weekday): self
    {
        $this->weekday = $weekday;

        return $this;
    }
}
