<?php

declare(strict_types=1);

namespace App\Command\Api;

use App\Security\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

trait CliAuthTrait
{
    public function authenticateCli(TokenStorageInterface $tokenStorage): void
    {
        $user = new User(
            accountId: 999,
            id: 1,
            username: 'cli-user',
            tokenIssuedAt: time(),
            permissions: [
                'lead' => [
                    'read',
                    'write',
                    'delete',
                    'import',
                    'export',
                ],
                'contact' => [
                    'read',
                    'write',
                    'delete',
                    'import',
                    'export',
                ],
                'deal' => [
                    'read',
                    'write',
                    'delete',
                    'import',
                    'export',
                ],
            ],
        );

        $token = new UsernamePasswordToken($user, 'api', ['ROLE_USER']);

        $tokenStorage->setToken($token);
    }
}
