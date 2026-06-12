<?php

namespace App\Controller\Api\V1;

use App\Controller\Api\ApiControllerTrait;
use App\Controller\Api\RequestMapper;
use App\DTO\Request\ReservationCreateRequest;
use App\DTO\Response\ReservationResponse;
use App\Entity\Reservation;
use App\Entity\User;
use App\Exception\ApiException;
use App\Repository\ReservationRepository;
use App\Security\RoleChecker;
use App\Service\ReservationService;
use App\Service\SlotService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class ReservationController
{
    use ApiControllerTrait;

    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly ReservationService $reservationService,
        private readonly SlotService $slotService,
        private readonly RequestMapper $requestMapper,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('/slots/available', methods: ['GET'])]
    public function searchAvailable(Request $request): JsonResponse
    {
        $date = $request->query->get('date');
        $startDt = $request->query->get('start_dt');
        $endDt = $request->query->get('end_dt');
        if (!$date || !$startDt || !$endDt) {
            throw new ApiException('date, start_dt and end_dt are required');
        }

        $targetDate = new \DateTimeImmutable($date);
        $start = new \DateTimeImmutable($startDt);
        $end = new \DateTimeImmutable($endDt);
        if ($start >= $end) {
            throw new ApiException('start_dt must be before end_dt');
        }

        $lat = $request->query->has('lat') ? (float) $request->query->get('lat') : null;
        $lng = $request->query->has('lng') ? (float) $request->query->get('lng') : null;
        $radiusKm = (float) ($request->query->get('radius_km') ?? 10.0);
        if ($radiusKm <= 0) {
            throw new ApiException('radius_km must be positive');
        }

        $slots = $this->slotService->findAvailableSlots($targetDate, $start, $end, $lat, $lng, $radiusKm);

        return $this->jsonResponse($slots, 200, $this->serializer);
    }

    #[Route('/reservations', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        RoleChecker::requireRenter($user);
        $data = $this->requestMapper->map($request, ReservationCreateRequest::class);
        $reservation = $this->reservationService->createReservation($user, $data);

        return $this->jsonResponse(ReservationResponse::fromEntity($reservation), Response::HTTP_CREATED, $this->serializer);
    }

    #[Route('/reservations', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $reservations = $this->reservationRepository->findBy(['renter' => $user]);

        return $this->jsonResponse(
            array_map(ReservationResponse::fromEntity(...), $reservations),
            200,
            $this->serializer
        );
    }

    #[Route('/reservations/{reservationId}', methods: ['GET'])]
    public function get(string $reservationId, #[CurrentUser] User $user): JsonResponse
    {
        $reservation = $this->findRenterReservation($reservationId, $user);

        return $this->jsonResponse(ReservationResponse::fromEntity($reservation), 200, $this->serializer);
    }

    #[Route('/reservations/{reservationId}/cancel', methods: ['POST'])]
    public function cancel(string $reservationId, #[CurrentUser] User $user): JsonResponse
    {
        RoleChecker::requireRenter($user);
        $reservation = $this->findRenterReservation($reservationId, $user);
        $reservation = $this->reservationService->cancelReservation($reservation);

        return $this->jsonResponse(ReservationResponse::fromEntity($reservation), 200, $this->serializer);
    }

    private function findRenterReservation(string $reservationId, User $user): Reservation
    {
        $reservation = $this->reservationRepository->findOneBy([
            'id' => Uuid::fromString($reservationId),
            'renter' => $user,
        ]);
        if (!$reservation) {
            throw new ApiException('Reservation not found', Response::HTTP_NOT_FOUND);
        }

        return $reservation;
    }
}
