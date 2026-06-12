<?php

namespace App\DTO\Response;

final readonly class TokenResponse
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public string $tokenType = 'bearer',
    ) {
    }
}
