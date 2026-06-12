<?php

namespace App\Controller\Api\V1;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'api' => 'v1',
        ]);
    }
}
