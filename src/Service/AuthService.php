<?php

namespace App\Service;

use App\DTO\Request\RegisterRequest;
use App\Entity\User;
use App\Enum\UserRole;
use App\Exception\ApiException;
use App\Repository\UserRepository;
use App\Security\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JwtService $jwtService,
    ) {
    }

    public function register(RegisterRequest $data, UserRole $role): User
    {
        if ($this->userRepository->findOneBy(['email' => $data->email])) {
            throw new ApiException('Unable to register with the provided credentials', Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data->email);
        $user->setFullName($data->fullName);
        $user->setRole($role);
        $user->setHashedPassword($this->passwordHasher->hashPassword($user, $data->password));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function authenticate(string $email, string $password): ?User
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return null;
        }

        return $user;
    }

    /** @return array{access: string, refresh: string} */
    public function issueTokens(User $user): array
    {
        return [
            'access' => $this->jwtService->createAccessToken($user),
            'refresh' => $this->jwtService->createRefreshToken($user),
        ];
    }
}
