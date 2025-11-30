<?php

declare(strict_types=1);

namespace App\Api\V1\EventListener;

use App\Api\V1\Response\ApiResponse;
use App\Exception\DomainException;
use App\Exception\InfrastructureException;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

#[AsEventListener(event: 'kernel.exception')]
final class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($response = $this->handleSpecialCases($exception)) {
            $event->setResponse($response);

            return;
        }

        $status = $this->resolveStatus($exception);
        $message = $this->resolveMessage($exception);

        $response = ApiResponse::error($message, status: $status);
        $event->setResponse(new JsonResponse($response->toArray(), $response->getStatus()));
    }

    /**
     * Handle special exception types that require custom formatting.
     */
    private function handleSpecialCases(Throwable $exception): ?JsonResponse
    {
        if ($exception instanceof UnauthorizedHttpException) {
            $response = ApiResponse::error(
                $exception->getMessage() ?: 'Unauthorized',
                status: 401
            );

            return new JsonResponse($response->toArray(), $response->getStatus());
        }

        if ($this->isValidationException($exception)) {
            /** @var ValidationFailedException $validationException */
            $validationException = $exception instanceof ValidationFailedException
                ? $exception
                : $exception->getPrevious();

            $violations = [];
            foreach ($validationException->getViolations() as $violation) {
                $violations[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            $message = $violations ? 'Validation failed' : $exception->getMessage();

            $response = ApiResponse::error(
                $message,
                errors: ['violations' => $violations],
                status: 422
            );

            return new JsonResponse($response->toArray(), $response->getStatus());
        }

        return null;
    }

    /**
     * Determines if the exception is a validation-related error.
     */
    private function isValidationException(Throwable $exception): bool
    {
        return $exception instanceof ValidationFailedException
            || ($exception instanceof UnprocessableEntityHttpException && $exception->getPrevious() instanceof ValidationFailedException);
    }

    /**
     * Map exception types to appropriate HTTP status codes.
     */
    private function resolveStatus(Throwable $exception): int
    {
        return match (true) {
            $exception instanceof LogicException => 400,
            $exception instanceof InvalidArgumentException,
            $exception instanceof DomainException => 422,
            $exception instanceof AccessDeniedException => 403,
            $exception instanceof InfrastructureException => 500,
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            default => 500,
        };
    }

    /**
     * Safely extract an exception message.
     */
    private function resolveMessage(Throwable $exception): string
    {
        $message = trim((string) $exception->getMessage());

        return '' !== $message ? $message : 'Unexpected error';
    }
}
