<?php

namespace App\Security;

use App\Entity\User;
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

    public function createAccessToken(User $user): string
    {
        return $this->encode($user, [
            'type' => 'access',
            'exp' => time() + ($this->accessTokenExpireMinutes * 60),
        ]);
    }

    public function createRefreshToken(User $user): string
    {
        return $this->encode($user, [
            'type' => 'refresh',
            'exp' => time() + ($this->refreshTokenExpireDays * 86400),
        ]);
    }

    /** @return array{sub: string, type: string, ver: int} */
    public function decode(string $token): array
    {
        $payload = JWT::decode($token, new Key($this->secret, self::ALGORITHM));

        return [
            'sub' => (string) $payload->sub,
            'type' => (string) $payload->type,
            'ver' => (int) ($payload->ver ?? 0),
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

    public function verifyTokenVersion(array $payload, User $user): void
    {
        if (($payload['ver'] ?? null) !== $user->getTokenVersion()) {
            throw new \InvalidArgumentException('Token has been revoked');
        }
    }

    /** @param array<string, mixed> $claims */
    private function encode(User $user, array $claims): string
    {
        return JWT::encode([
            'sub' => (string) $user->getId(),
            'ver' => $user->getTokenVersion(),
            ...$claims,
        ], $this->secret, self::ALGORITHM);
    }
}
