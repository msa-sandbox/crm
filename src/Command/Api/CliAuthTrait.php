<?php

declare(strict_types=1);

namespace App\Command\Api;

use App\Security\User;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

trait CliAuthTrait
{
    public function authenticateCli(TokenStorageInterface $tokenStorage): void
    {
        $user = new User(
            1,
            999,
            'cli-user',
            time(),
            [
                "lead" => [
                    "read",
                    "write",
                    "delete",
                    "import",
                    "export"
                ],
                "contact" => [
                    "read",
                    "write",
                    "delete",
                    "import",
                    "export"
                ],
                "deal" => [
                    "read",
                    "write",
                    "delete",
                    "import",
                    "export"
                ]
            ],
        );

        $token = new UsernamePasswordToken($user, 'api', ['ROLE_USER']);

        $tokenStorage->setToken($token);
    }
}
