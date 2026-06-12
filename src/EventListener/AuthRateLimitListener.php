<?php

namespace App\EventListener;

use App\Exception\ApiException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
final class AuthRateLimitListener
{
    public function __construct(
        private readonly RateLimiterFactory $authLimiter,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isAuthMutation($request)) {
            return;
        }

        $limiter = $this->authLimiter->create($request->getClientIp() ?? 'unknown');
        if (!$limiter->consume()->isAccepted()) {
            throw new ApiException('Too many requests', Response::HTTP_TOO_MANY_REQUESTS);
        }
    }

    private function isAuthMutation(Request $request): bool
    {
        if (!in_array($request->getMethod(), ['POST', 'PATCH', 'PUT'], true)) {
            return false;
        }

        return str_starts_with($request->getPathInfo(), '/api/v1/auth');
    }
}
