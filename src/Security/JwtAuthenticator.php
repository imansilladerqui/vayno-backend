<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Uid\Uuid;

final class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly JwtService $jwtService,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if (!$this->hasBearerToken($request)) {
            return false;
        }

        if ($this->isPublicPath($request)) {
            return false;
        }

        return true;
    }

    private function hasBearerToken(Request $request): bool
    {
        $header = $request->headers->get('Authorization', '');

        return str_starts_with($header, 'Bearer ') && trim(substr($header, 7)) !== '';
    }

    private function isPublicPath(Request $request): bool
    {
        $path = $request->getPathInfo();

        return (bool) preg_match(
            '#^/api/v1(?:/?|/auth/(?:register|register-owner|login|refresh)|/slots/available)$#',
            $path
        );
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $header = $request->headers->get('Authorization', '');
        $token = trim(substr($header, 7));

        try {
            $payload = $this->jwtService->decode($token);
            $userId = $this->jwtService->verifyType($payload, 'access');
        } catch (\Throwable) {
            throw new AuthenticationException('Could not validate credentials');
        }

        return new SelfValidatingPassport(
            new UserBadge($userId, function (string $id) use ($payload): ?User {
                $user = $this->userRepository->find(Uuid::fromString($id));
                if (!$user) {
                    return null;
                }

                try {
                    $this->jwtService->verifyTokenVersion($payload, $user);
                } catch (\Throwable) {
                    return null;
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['detail' => 'Could not validate credentials'], Response::HTTP_UNAUTHORIZED);
    }
}
