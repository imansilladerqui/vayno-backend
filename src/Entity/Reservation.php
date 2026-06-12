<?php

namespace App\Entity;

use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservations')]
class Reservation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: ParkingSlot::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'slot_id', nullable: false, onDelete: 'CASCADE')]
    private ParkingSlot $slot;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'renter_id', nullable: false, onDelete: 'CASCADE')]
    private User $renter;

    #[ORM\Column(name: 'start_dt', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $startDt;

    #[ORM\Column(name: 'end_dt', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $endDt;

    #[ORM\Column(enumType: ReservationStatus::class)]
    private ReservationStatus $status;

    #[ORM\Column(name: 'total_price', nullable: true)]
    private ?float $totalPrice = null;

    #[ORM\Column(name: 'qr_code', length: 255, unique: true)]
    private string $qrCode;

    /** @var Collection<int, CheckEvent> */
    #[ORM\OneToMany(targetEntity: CheckEvent::class, mappedBy: 'reservation', orphanRemoval: true)]
    private Collection $checkEvents;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->status = ReservationStatus::Pending;
        $this->checkEvents = new ArrayCollection();
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

    public function getRenter(): User
    {
        return $this->renter;
    }

    public function setRenter(User $renter): self
    {
        $this->renter = $renter;

        return $this;
    }

    public function getStartDt(): \DateTimeImmutable
    {
        return $this->startDt;
    }

    public function setStartDt(\DateTimeImmutable $startDt): self
    {
        $this->startDt = $startDt;

        return $this;
    }

    public function getEndDt(): \DateTimeImmutable
    {
        return $this->endDt;
    }

    public function setEndDt(\DateTimeImmutable $endDt): self
    {
        $this->endDt = $endDt;

        return $this;
    }

    public function getStatus(): ReservationStatus
    {
        return $this->status;
    }

    public function setStatus(ReservationStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getQrCode(): string
    {
        return $this->qrCode;
    }

    public function setQrCode(string $qrCode): self
    {
        $this->qrCode = $qrCode;

        return $this;
    }
}
