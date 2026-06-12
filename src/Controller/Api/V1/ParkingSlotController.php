<?php

namespace App\Controller\Api\V1;

use App\Controller\Api\ApiControllerTrait;
use App\Controller\Api\RequestMapper;
use App\DTO\Request\ParkingSlotCreateRequest;
use App\DTO\Request\ParkingSlotUpdateRequest;
use App\DTO\Response\ParkingSlotResponse;
use App\Entity\ParkingLot;
use App\Entity\ParkingSlot;
use App\Entity\User;
use App\Exception\ApiException;
use App\Repository\ParkingLotRepository;
use App\Repository\ParkingSlotRepository;
use App\Security\RoleChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class ParkingSlotController
{
    use ApiControllerTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ParkingLotRepository $lotRepository,
        private readonly ParkingSlotRepository $slotRepository,
        private readonly RequestMapper $requestMapper,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('/lots/{lotId}/slots', methods: ['POST'])]
    public function create(string $lotId, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        RoleChecker::requireOwner($user);
        $lot = $this->findOwnedLot($lotId, $user);
        $data = $this->requestMapper->map($request, ParkingSlotCreateRequest::class);

        $slot = new ParkingSlot();
        $slot->setLot($lot);
        $slot->setSlotNumber($data->slotNumber);
        $slot->setSlotType($data->slotType);
        $slot->setPricePerHour($data->pricePerHour);
        $slot->setIsActive($data->isActive);

        $this->em->persist($slot);
        $this->em->flush();

        return $this->jsonResponse(ParkingSlotResponse::fromEntity($slot), Response::HTTP_CREATED, $this->serializer);
    }

    #[Route('/lots/{lotId}/slots', methods: ['GET'])]
    public function list(string $lotId, #[CurrentUser] User $user): JsonResponse
    {
        RoleChecker::requireOwner($user);
        $this->findOwnedLot($lotId, $user);
        $slots = $this->slotRepository->findBy(['lot' => Uuid::fromString($lotId)]);

        return $this->jsonResponse(
            array_map(ParkingSlotResponse::fromEntity(...), $slots),
            200,
            $this->serializer
        );
    }

    #[Route('/slots/{slotId}', methods: ['PATCH'])]
    public function update(string $slotId, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        RoleChecker::requireOwner($user);
        $slot = $this->findOwnedSlot($slotId, $user);
        $data = $this->requestMapper->map($request, ParkingSlotUpdateRequest::class);

        if ($data->slotNumber !== null) {
            $slot->setSlotNumber($data->slotNumber);
        }
        if ($data->slotType !== null) {
            $slot->setSlotType($data->slotType);
        }
        if ($data->pricePerHour !== null) {
            $slot->setPricePerHour($data->pricePerHour);
        }
        if ($data->isActive !== null) {
            $slot->setIsActive($data->isActive);
        }

        $this->em->flush();

        return $this->jsonResponse(ParkingSlotResponse::fromEntity($slot), 200, $this->serializer);
    }

    #[Route('/slots/{slotId}', methods: ['DELETE'])]
    public function delete(string $slotId, #[CurrentUser] User $user): Response
    {
        RoleChecker::requireOwner($user);
        $slot = $this->findOwnedSlot($slotId, $user);
        $this->em->remove($slot);
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

    private function findOwnedSlot(string $slotId, User $user): ParkingSlot
    {
        $slot = $this->slotRepository->createQueryBuilder('s')
            ->join('s.lot', 'l')
            ->where('s.id = :slotId')
            ->andWhere('l.owner = :owner')
            ->setParameter('slotId', Uuid::fromString($slotId))
            ->setParameter('owner', $user)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$slot) {
            throw new ApiException('Slot not found', Response::HTTP_NOT_FOUND);
        }

        return $slot;
    }
}
