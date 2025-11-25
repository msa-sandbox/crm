<?php

declare(strict_types=1);

namespace App\Api\V1\EventListener\Middleware;

use App\Security\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Important to check how many requests can be made by a user in a given time window.
 * Priority is set to 6 to make this listener run after authentication.
 */
#[AsEventListener(event: 'kernel.request', priority: 6)]
readonly class RateLimitPerUserListener
{
    public function __construct(
        private RateLimiterFactory $apiPerUserLimiter,
        private Security $security,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user) {
            // Again some public endpoint.
            return;
        }

        $limiter = $this->apiPerUserLimiter->create((string) $user->getId());
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException('Limit is exceeded, try next second');
        }
    }
}
