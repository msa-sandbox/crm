<?php

declare(strict_types=1);

namespace App\Api\V1\EventListener\Middleware;

use App\Security\User;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * If someone changed user permissions - we should block the request and ask the user to refresh tokens.
 *
 * Priority is set to 7 to make this listener run AFTER authorization.
 * Auth firewall is 8 by default (bin/console debug:event-dispatcher kernel.request -> )
 */
#[AsEventListener(event: 'kernel.request', priority: 7)]
readonly class RequestCheckUserPermissionListener
{
    public function __construct(
        private Security $security,
        #[Autowire(service: 'App\Cache\AuthCache')]
        private CacheInterface $authCache,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     * @throws UnauthorizedHttpException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user) {
            // Most likely we are within some public endpoint.
            return;
        }

        $value = $this->authCache->get("invalidated_{$user->getId()}");

        // If there is no data about changed permissions -- just pass
        if (!$value) {
            return;
        }

        if ($value <= time()) {
            throw new UnauthorizedHttpException('Token has been invalidated, please refresh');
        }
    }
}
