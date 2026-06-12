<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RequestMapper
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function map(Request $request, string $class): object
    {
        $dto = $this->serializer->deserialize($request->getContent(), $class, 'json');
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new \Symfony\Component\Validator\Exception\ValidationFailedException($dto, $violations);
        }

        return $dto;
    }
}
