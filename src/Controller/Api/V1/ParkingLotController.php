<?php

namespace App\Controller\Api\V1;

use App\Controller\Api\ApiControllerTrait;
use App\Controller\Api\RequestMapper;
use App\DTO\Request\ParkingLotCreateRequest;
use App\DTO\Request\ParkingLotUpdateRequest;
use App\DTO\Response\ParkingLotResponse;
use App\Entity\ParkingLot;
use App\Entity\User;
use App\Exception\ApiException;
use App\Repository\ParkingLotRepository;
use App\Security\RoleChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

#[Route('/lots')]
final class ParkingLotController
{
    use ApiControllerTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ParkingLotRepository $lotRepository,
        private readonly RequestMapper $requestMapper,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        RoleChecker::requireOwner($user);
        $data = $this->requestMapper->map($request, ParkingLotCreateRequest::class);

        $lot = new ParkingLot();
        $lot->setOwner($user);
        $lot->setName($data->name);
        $lot->setAddress($data->address);
        $lot->setLat($data->lat);
        $lot->setLng($data->lng);
        $lot->setIsActive($data->isActive);

        $this->em->persist($lot);
        $this->em->flush();

        return $this->jsonResponse(ParkingLotResponse::fromEntity($lot), Response::HTTP_CREATED, $this->serializer);
    }

    #[Route('', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        RoleChecker::requireOwner($user);
        $lots = $this->lotRepository->findBy(['owner' => $user]);

        return $this->jsonResponse(
            array_map(ParkingLotResponse::fromEntity(...), $lots),
            200,
            $this->serializer
        );
    }

    #[Route('/{lotId}', methods: ['GET'])]
    public function get(string $lotId, #[CurrentUser] User $user): JsonResponse
    {
        RoleChecker::requireOwner($user);
        $lot = $this->findOwnedLot($lotId, $user);

        return $this->jsonResponse(ParkingLotResponse::fromEntity($lot), 200, $this->serializer);
    }

    #[Route('/{lotId}', methods: ['PATCH'])]
    public function update(string $lotId, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        RoleChecker::requireOwner($user);
        $lot = $this->findOwnedLot($lotId, $user);
        $data = $this->requestMapper->map($request, ParkingLotUpdateRequest::class);

        if ($data->name !== null) {
            $lot->setName($data->name);
        }
        if ($data->address !== null) {
            $lot->setAddress($data->address);
        }
        if ($data->lat !== null) {
            $lot->setLat($data->lat);
        }
        if ($data->lng !== null) {
            $lot->setLng($data->lng);
        }
        if ($data->isActive !== null) {
            $lot->setIsActive($data->isActive);
        }

        $this->em->flush();

        return $this->jsonResponse(ParkingLotResponse::fromEntity($lot), 200, $this->serializer);
    }

    #[Route('/{lotId}', methods: ['DELETE'])]
    public function delete(string $lotId, #[CurrentUser] User $user): Response
    {
        RoleChecker::requireOwner($user);
        $lot = $this->findOwnedLot($lotId, $user);
        $this->em->remove($lot);
        $this->em->flush();

        return $this->noContent();
    }

    private function findOwnedLot(string $lotId, User $user): ParkingLot
    {
        $lot = $this->lotRepository->findOneBy(['id' => Uuid::fromString($lotId), 'owner' => $user]);
        if (!$lot) {
            throw new ApiException('Lot not found', Response::HTTP_NOT_FOUND);
        }

        return $lot;
    }
}
