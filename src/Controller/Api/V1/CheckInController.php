<?php

namespace App\Controller\Api\V1;

use App\Controller\Api\ApiControllerTrait;
use App\Controller\Api\RequestMapper;
use App\DTO\Request\CheckInRequest;
use App\DTO\Response\ReceiptResponse;
use App\DTO\Response\ReservationResponse;
use App\Entity\Reservation;
use App\Entity\User;
use App\Exception\ApiException;
use App\Repository\ReservationRepository;
use App\Security\RoleChecker;
use App\Service\CheckInService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class CheckInController
{
    use ApiControllerTrait;

    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly CheckInService $checkInService,
        private readonly RequestMapper $requestMapper,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('/reservations/{reservationId}/checkin', methods: ['POST'])]
    public function checkin(string $reservationId, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        RoleChecker::requireRenter($user);
        $reservation = $this->findRenterReservation($reservationId, $user);
        $data = $this->requestMapper->map($request, CheckInRequest::class);
        $reservation = $this->checkInService->checkIn($reservation, $data->qrCode);

        return $this->jsonResponse(ReservationResponse::fromEntity($reservation), 200, $this->serializer);
    }

    #[Route('/reservations/{reservationId}/checkout', methods: ['POST'])]
    public function checkout(string $reservationId, #[CurrentUser] User $user): JsonResponse
    {
        RoleChecker::requireRenter($user);
        $reservation = $this->findRenterReservation($reservationId, $user);
        $receipt = $this->checkInService->checkOut($reservation);

        return $this->jsonResponse($receipt, 200, $this->serializer);
    }

    private function findRenterReservation(string $reservationId, User $user): Reservation
    {
        $reservation = $this->reservationRepository->createQueryBuilder('r')
            ->join('r.slot', 's')
            ->addSelect('s')
            ->where('r.id = :id')
            ->andWhere('r.renter = :renter')
            ->setParameter('id', Uuid::fromString($reservationId))
            ->setParameter('renter', $user)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$reservation) {
            throw new ApiException('Reservation not found', Response::HTTP_NOT_FOUND);
        }

        return $reservation;
    }
}
