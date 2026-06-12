<?php

namespace App\Controller\Api\V1;

use App\Controller\Api\ApiControllerTrait;
use App\DTO\Response\OwnerReservationResponse;
use App\Entity\User;
use App\Exception\ApiException;
use App\Repository\ReservationRepository;
use App\Security\RoleChecker;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

#[Route('/owner')]
final class OwnerReservationController
{
    use ApiControllerTrait;

    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('/reservations', methods: ['GET'])]
    public function list(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        RoleChecker::requireOwner($user);

        $qb = $this->reservationRepository->createQueryBuilder('r')
            ->join('r.slot', 's')
            ->join('s.lot', 'l')
            ->join('r.renter', 'renter')
            ->addSelect('s', 'l', 'renter')
            ->where('l.owner = :owner')
            ->setParameter('owner', $user)
            ->orderBy('r.startDt', 'DESC');

        $lotId = $request->query->get('lot_id');
        if ($lotId) {
            $qb->andWhere('l.id = :lotId')->setParameter('lotId', Uuid::fromString($lotId));
        }

        $reservations = $qb->getQuery()->getResult();

        return $this->jsonResponse(
            array_map(OwnerReservationResponse::fromEntity(...), $reservations),
            200,
            $this->serializer
        );
    }

    #[Route('/reservations/{reservationId}', methods: ['GET'])]
    public function get(string $reservationId, #[CurrentUser] User $user): JsonResponse
    {
        RoleChecker::requireOwner($user);

        $reservation = $this->reservationRepository->createQueryBuilder('r')
            ->join('r.slot', 's')
            ->join('s.lot', 'l')
            ->join('r.renter', 'renter')
            ->addSelect('s', 'l', 'renter')
            ->where('r.id = :id')
            ->andWhere('l.owner = :owner')
            ->setParameter('id', Uuid::fromString($reservationId))
            ->setParameter('owner', $user)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$reservation) {
            throw new ApiException('Reservation not found', Response::HTTP_NOT_FOUND);
        }

        return $this->jsonResponse(OwnerReservationResponse::fromEntity($reservation), 200, $this->serializer);
    }
}
