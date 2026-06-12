<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

trait ApiControllerTrait
{
    private function jsonResponse(mixed $data, int $status = Response::HTTP_OK, SerializerInterface $serializer): JsonResponse
    {
        $json = $serializer->serialize($data, 'json', [
            'datetime_format' => \DateTimeInterface::ATOM,
        ]);

        return new JsonResponse($json, $status, [], true);
    }

    private function noContent(): Response
    {
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
