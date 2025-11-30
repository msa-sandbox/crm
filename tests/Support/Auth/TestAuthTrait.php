<?php

declare(strict_types=1);

namespace App\Tests\Support\Auth;

use App\Security\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

trait TestAuthTrait
{
    protected function authenticateTestUser(TokenStorageInterface $tokenStorage): User
    {
        $user = new User(
            accountId: 999,
            id: 1,
            username: 'test-user',
            tokenIssuedAt: time(),
            permissions: [
                'lead' => ['read', 'write', 'delete', 'import', 'export'],
                'contact' => ['read', 'write', 'delete', 'import', 'export'],
                'deal' => ['read', 'write', 'delete', 'import', 'export'],
            ],
        );

        $token = new UsernamePasswordToken($user, 'api', ['ROLE_USER']);
        $tokenStorage->setToken($token);

        return $user;
    }
}
