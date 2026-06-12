<?php

namespace App\Controller\Api\V1;

use App\Controller\Api\ApiControllerTrait;
use App\Controller\Api\RequestMapper;
use App\DTO\Request\UserUpdateRequest;
use App\DTO\Response\UserResponse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/users')]
final class UserController
{
    use ApiControllerTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestMapper $requestMapper,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('/me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return $this->jsonResponse(UserResponse::fromEntity($user), 200, $this->serializer);
    }

    #[Route('/me', methods: ['PATCH'])]
    public function updateMe(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = $this->requestMapper->map($request, UserUpdateRequest::class);

        if ($data->fullName !== null) {
            $user->setFullName($data->fullName);
        }
        if ($data->role !== null) {
            $user->setRole($data->role);
        }

        $this->em->flush();

        return $this->jsonResponse(UserResponse::fromEntity($user), 200, $this->serializer);
    }
}
