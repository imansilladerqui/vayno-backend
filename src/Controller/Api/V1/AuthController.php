<?php

namespace App\Controller\Api\V1;

use App\Controller\Api\ApiControllerTrait;
use App\Controller\Api\RequestMapper;
use App\DTO\Request\LoginRequest;
use App\DTO\Request\RefreshRequest;
use App\DTO\Request\RegisterRequest;
use App\DTO\Response\TokenResponse;
use App\DTO\Response\UserResponse;
use App\Enum\UserRole;
use App\Exception\ApiException;
use App\Repository\UserRepository;
use App\Security\JwtService;
use App\Service\AuthService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

#[Route('/auth')]
final class AuthController
{
    use ApiControllerTrait;

    public function __construct(
        private readonly AuthService $authService,
        private readonly JwtService $jwtService,
        private readonly UserRepository $userRepository,
        private readonly RequestMapper $requestMapper,
        private readonly SerializerInterface $serializer,
        private readonly bool $allowOwnerRegistration,
    ) {
    }

    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = $this->requestMapper->map($request, RegisterRequest::class);
        $user = $this->authService->register($data, UserRole::Renter);

        return $this->jsonResponse(UserResponse::fromEntity($user), Response::HTTP_CREATED, $this->serializer);
    }

    #[Route('/register-owner', methods: ['POST'])]
    public function registerOwner(Request $request): JsonResponse
    {
        if (!$this->allowOwnerRegistration) {
            throw new ApiException('Owner registration is disabled', Response::HTTP_FORBIDDEN);
        }

        $data = $this->requestMapper->map($request, RegisterRequest::class);
        $user = $this->authService->register($data, UserRole::Owner);

        return $this->jsonResponse(UserResponse::fromEntity($user), Response::HTTP_CREATED, $this->serializer);
    }

    #[Route('/login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = $this->requestMapper->map($request, LoginRequest::class);
        $user = $this->authService->authenticate($data->email, $data->password);
        if (!$user) {
            throw new ApiException('Incorrect email or password', Response::HTTP_UNAUTHORIZED);
        }

        $tokens = $this->authService->issueTokens($user);

        return $this->jsonResponse(new TokenResponse($tokens['access'], $tokens['refresh']), Response::HTTP_OK, $this->serializer);
    }

    #[Route('/refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = $this->requestMapper->map($request, RefreshRequest::class);

        try {
            $payload = $this->jwtService->decode($data->refreshToken);
            $userId = $this->jwtService->verifyType($payload, 'refresh');
            $user = $this->userRepository->find(Uuid::fromString($userId));
            if (!$user) {
                throw new \InvalidArgumentException('User not found');
            }
            $this->jwtService->verifyTokenVersion($payload, $user);
        } catch (\Throwable) {
            throw new ApiException('Invalid refresh token', Response::HTTP_UNAUTHORIZED);
        }

        return $this->jsonResponse(
            new TokenResponse(
                $this->jwtService->createAccessToken($user),
                $this->jwtService->createRefreshToken($user),
            ),
            Response::HTTP_OK,
            $this->serializer
        );
    }
}
