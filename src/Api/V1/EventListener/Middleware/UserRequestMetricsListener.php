<?php

declare(strict_types=1);

namespace App\Api\V1\EventListener\Middleware;

use App\Security\User;
use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(event: 'kernel.request', priority: 5)]
readonly class UserRequestMetricsListener
{
    public function __construct(
        private Security $security,
        private CollectorRegistry $registry,
    ) {
    }

    /**
     * @throws MetricsRegistrationException
     */
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

        $counter = $this->registry->getOrRegisterCounter(
            'api',
            'user_requests_total',
            'Total API requests per user',
            ['user_id']
        );

        $counter->inc([(string) $user->getId()]);
    }
}
