<?php

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\Uid\Uuid;

final class JwtService
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly int $accessTokenExpireMinutes,
        private readonly int $refreshTokenExpireDays,
    ) {
    }

    public function createAccessToken(Uuid $userId): string
    {
        return $this->encode([
            'sub' => (string) $userId,
            'type' => 'access',
            'exp' => time() + ($this->accessTokenExpireMinutes * 60),
        ]);
    }

    public function createRefreshToken(Uuid $userId): string
    {
        return $this->encode([
            'sub' => (string) $userId,
            'type' => 'refresh',
            'exp' => time() + ($this->refreshTokenExpireDays * 86400),
        ]);
    }

    /** @return array{sub: string, type: string} */
    public function decode(string $token): array
    {
        $payload = JWT::decode($token, new Key($this->secret, self::ALGORITHM));

        return [
            'sub' => (string) $payload->sub,
            'type' => (string) $payload->type,
        ];
    }

    public function verifyType(array $payload, string $expectedType): string
    {
        if (($payload['type'] ?? null) !== $expectedType) {
            throw new \InvalidArgumentException('Invalid token type');
        }

        $subject = $payload['sub'] ?? null;
        if (!$subject) {
            throw new \InvalidArgumentException('Missing subject');
        }

        return $subject;
    }

    /** @param array<string, mixed> $payload */
    private function encode(array $payload): string
    {
        return JWT::encode($payload, $this->secret, self::ALGORITHM);
    }
}
