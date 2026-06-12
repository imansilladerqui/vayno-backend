<?php

namespace App\Controller\Api\V1;

use App\Controller\Api\ApiControllerTrait;
use App\Controller\Api\RequestMapper;
use App\DTO\Request\SlotAvailabilityCreateRequest;
use App\DTO\Response\SlotAvailabilityResponse;
use App\Entity\SlotAvailability;
use App\Entity\User;
use App\Exception\ApiException;
use App\Repository\SlotAvailabilityRepository;
use App\Security\RoleChecker;
use App\Service\SlotService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class AvailabilityController
{
    use ApiControllerTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SlotAvailabilityRepository $availabilityRepository,
        private readonly SlotService $slotService,
        private readonly RequestMapper $requestMapper,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('/slots/{slotId}/availability', methods: ['POST'])]
    public function create(string $slotId, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        RoleChecker::requireOwner($user);
        $slot = $this->slotService->getSlotForOwner(Uuid::fromString($slotId), $user->getId());
        if (!$slot) {
            throw new ApiException('Slot not found', Response::HTTP_NOT_FOUND);
        }

        $data = $this->requestMapper->map($request, SlotAvailabilityCreateRequest::class);
        $this->slotService->validateAvailabilityOverlap($slot->getId(), $data);

        $window = $this->slotService->createAvailabilityEntity($slot, $data);
        $this->em->persist($window);
        $this->em->flush();

        return $this->jsonResponse(SlotAvailabilityResponse::fromEntity($window), Response::HTTP_CREATED, $this->serializer);
    }

    #[Route('/slots/{slotId}/availability', methods: ['GET'])]
    public function list(string $slotId, #[CurrentUser] User $user): JsonResponse
    {
        RoleChecker::requireOwner($user);
        $slot = $this->slotService->getSlotForOwner(Uuid::fromString($slotId), $user->getId());
        if (!$slot) {
            throw new ApiException('Slot not found', Response::HTTP_NOT_FOUND);
        }

        $windows = $this->availabilityRepository->findBy(['slot' => $slot]);

        return $this->jsonResponse(
            array_map(SlotAvailabilityResponse::fromEntity(...), $windows),
            200,
            $this->serializer
        );
    }

    #[Route('/availability/{availabilityId}', methods: ['DELETE'])]
    public function delete(string $availabilityId, #[CurrentUser] User $user): Response
    {
        RoleChecker::requireOwner($user);
        $window = $this->availabilityRepository->createQueryBuilder('w')
            ->join('w.slot', 's')
            ->join('s.lot', 'l')
            ->where('w.id = :id')
            ->andWhere('l.owner = :owner')
            ->setParameter('id', Uuid::fromString($availabilityId))
            ->setParameter('owner', $user)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$window instanceof SlotAvailability) {
            throw new ApiException('Availability not found', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($window);
        $this->em->flush();

        return $this->noContent();
    }
}
