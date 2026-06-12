<?php

namespace App\EventListener;

use App\Exception\ApiException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionListener
{
    public function __construct(
        private readonly bool $debug,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof \InvalidArgumentException) {
            $event->setResponse(new JsonResponse(
                ['detail' => $exception->getMessage()],
                Response::HTTP_BAD_REQUEST
            ));

            return;
        }

        if ($exception instanceof ApiException) {
            $event->setResponse(new JsonResponse(
                ['detail' => $exception->getMessage()],
                $exception->getStatusCode()
            ));

            return;
        }

        if ($exception instanceof AccessDeniedHttpException) {
            $event->setResponse(new JsonResponse(
                ['detail' => $exception->getMessage() ?: 'Access denied'],
                Response::HTTP_FORBIDDEN
            ));

            return;
        }

        if ($exception instanceof ValidationFailedException) {
            $violations = [];
            foreach ($exception->getViolations() as $violation) {
                $violations[] = ['msg' => $violation->getMessage()];
            }
            $event->setResponse(new JsonResponse(
                ['detail' => $violations],
                Response::HTTP_UNPROCESSABLE_ENTITY
            ));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse(
                ['detail' => $exception->getMessage() ?: Response::$statusTexts[$exception->getStatusCode()] ?? 'Error'],
                $exception->getStatusCode()
            ));

            return;
        }

        if (!$this->debug) {
            $event->setResponse(new JsonResponse(
                ['detail' => 'Internal server error'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            ));
        }
    }
}
