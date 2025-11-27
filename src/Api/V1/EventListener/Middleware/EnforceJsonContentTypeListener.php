<?php

declare(strict_types=1);

namespace App\Api\V1\EventListener\Middleware;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * All POST and PUT requests must have the `Content-Type: application/json` header.
 */
#[AsEventListener(event: 'kernel.request', priority: -100)]
class EnforceJsonContentTypeListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->isMethod('POST') || $request->isMethod('PUT')) {
            if ('application/json' !== $request->headers->get('Content-Type', '')) {
                throw new UnsupportedMediaTypeHttpException('Content-Type must be application/json');
            }
        }
    }
}
