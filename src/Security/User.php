<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * We do not have any user table here.
 * Everything is set from the API token.
 */
readonly class User implements UserInterface
{
    public function __construct(
        private int $id,
        private string $username,
        private array $permissions,
    )
    {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }


    /**
     * We are not using roles, only permissions.
     *
     * @return string[]
     */
    public function getRoles(): array
    {
        return [];
    }

    public function eraseCredentials(): void
    {
        //
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }
}
