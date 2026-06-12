<?php

namespace App\Entity;

use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255, unique: true)]
    private string $email;

    #[ORM\Column(name: 'hashed_password', length: 255)]
    private string $hashedPassword;

    #[ORM\Column(name: 'full_name', length: 255)]
    private string $fullName;

    #[ORM\Column(enumType: UserRole::class)]
    private UserRole $role;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'token_version', options: ['default' => 1])]
    private int $tokenVersion = 1;

    /** @var Collection<int, ParkingLot> */
    #[ORM\OneToMany(targetEntity: ParkingLot::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $parkingLots;

    /** @var Collection<int, Reservation> */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'renter', orphanRemoval: true)]
    private Collection $reservations;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->parkingLots = new ArrayCollection();
        $this->reservations = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getPassword(): string
    {
        return $this->hashedPassword;
    }

    public function setHashedPassword(string $hashedPassword): self
    {
        $this->hashedPassword = $hashedPassword;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getTokenVersion(): int
    {
        return $this->tokenVersion;
    }

    public function incrementTokenVersion(): void
    {
        ++$this->tokenVersion;
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner || $this->role === UserRole::Both;
    }

    public function isRenter(): bool
    {
        return $this->role === UserRole::Renter || $this->role === UserRole::Both;
    }
}
