<?php

declare(strict_types=1);

namespace App\Api\V1\EventListener;

use App\Api\V1\Response\ApiResponse;
use App\Exception\AuthException;
use App\Exception\InfrastructureException;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

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

        if ($exception instanceof LogicException) {
            $status = $exception instanceof InvalidArgumentException ? 422 : 400;

            $response = ApiResponse::error($exception->getMessage(), status: $status);

            $event->setResponse(new JsonResponse(
                $response->toArray(),
                $response->getStatus()
            ));
        }

        if ($exception instanceof AuthException) {
            $response = ApiResponse::error($exception->getMessage(), status: 401);

            $event->setResponse(new JsonResponse(
                $response->toArray(),
                $response->getStatus()
            ));
        }

        if ($exception instanceof InfrastructureException) {
            $response = ApiResponse::error('Internal error, try later', status: 500);

            $event->setResponse(new JsonResponse(
                $response->toArray(),
                $response->getStatus()
            ));
        }
    }
}
