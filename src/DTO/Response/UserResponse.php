<?php

namespace App\DTO\Response;

use App\Entity\User;
use App\Enum\UserRole;

final readonly class UserResponse
{
    public function __construct(
        public string $id,
        public string $email,
        public string $fullName,
        public UserRole $role,
        public \DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            (string) $user->getId(),
            $user->getEmail(),
            $user->getFullName(),
            $user->getRole(),
            $user->getCreatedAt(),
        );
    }
}
