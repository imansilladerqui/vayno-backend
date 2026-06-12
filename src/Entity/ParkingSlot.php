<?php

namespace App\Entity;

use App\Enum\SlotType;
use App\Repository\ParkingSlotRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ParkingSlotRepository::class)]
#[ORM\Table(name: 'parking_slots')]
class ParkingSlot
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: ParkingLot::class, inversedBy: 'slots')]
    #[ORM\JoinColumn(name: 'lot_id', nullable: false, onDelete: 'CASCADE')]
    private ParkingLot $lot;

    #[ORM\Column(name: 'slot_number', length: 50)]
    private string $slotNumber;

    #[ORM\Column(name: 'slot_type', enumType: SlotType::class)]
    private SlotType $slotType;

    #[ORM\Column(name: 'price_per_hour')]
    private float $pricePerHour;

    #[ORM\Column(name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    /** @var Collection<int, SlotAvailability> */
    #[ORM\OneToMany(targetEntity: SlotAvailability::class, mappedBy: 'slot', orphanRemoval: true)]
    private Collection $availabilityWindows;

    /** @var Collection<int, Reservation> */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'slot', orphanRemoval: true)]
    private Collection $reservations;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->availabilityWindows = new ArrayCollection();
        $this->reservations = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getLot(): ParkingLot
    {
        return $this->lot;
    }

    public function setLot(ParkingLot $lot): self
    {
        $this->lot = $lot;

        return $this;
    }

    public function getSlotNumber(): string
    {
        return $this->slotNumber;
    }

    public function setSlotNumber(string $slotNumber): self
    {
        $this->slotNumber = $slotNumber;

        return $this;
    }

    public function getSlotType(): SlotType
    {
        return $this->slotType;
    }

    public function setSlotType(SlotType $slotType): self
    {
        $this->slotType = $slotType;

        return $this;
    }

    public function getPricePerHour(): float
    {
        return $this->pricePerHour;
    }

    public function setPricePerHour(float $pricePerHour): self
    {
        $this->pricePerHour = $pricePerHour;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    /** @return Collection<int, SlotAvailability> */
    public function getAvailabilityWindows(): Collection
    {
        return $this->availabilityWindows;
    }
}
