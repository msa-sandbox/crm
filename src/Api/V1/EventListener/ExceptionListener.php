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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * We want to customize some exceptions since they are logical cases and not exceptions.
 * For example, some unicity constraints.
 */
#[AsEventListener(event: 'kernel.exception')]
final class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $status = match (true) {
            $exception instanceof LogicException => 400,
            $exception instanceof AccessDeniedException => 403,
            $exception instanceof InvalidArgumentException => 422,
            $exception instanceof DomainException => 422,
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            $exception instanceof InfrastructureException => 500,
            default => 500,
        };

        $message = $exception->getMessage() ?: 'Unexpected error';

        $response = ApiResponse::error($message, status: $status);
        $event->setResponse(new JsonResponse($response->toArray(), $response->getStatus()));
    }
}
