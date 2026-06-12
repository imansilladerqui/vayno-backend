<?php

namespace App\Entity;

use App\Enum\CheckEventType;
use App\Repository\CheckEventRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CheckEventRepository::class)]
#[ORM\Table(name: 'check_events')]
class CheckEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Reservation::class, inversedBy: 'checkEvents')]
    #[ORM\JoinColumn(name: 'reservation_id', nullable: false, onDelete: 'CASCADE')]
    private Reservation $reservation;

    #[ORM\Column(name: 'event_type', enumType: CheckEventType::class)]
    private CheckEventType $eventType;

    #[ORM\Column(type: 'datetimetz_immutable')]
    private \DateTimeImmutable $timestamp;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->timestamp = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getReservation(): Reservation
    {
        return $this->reservation;
    }

    public function setReservation(Reservation $reservation): self
    {
        $this->reservation = $reservation;

        return $this;
    }

    public function getEventType(): CheckEventType
    {
        return $this->eventType;
    }

    public function setEventType(CheckEventType $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeImmutable $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}
