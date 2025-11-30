<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class UserTest extends TestCase
{
    public function testImplementsUserInterface(): void
    {
        $user = new User(10, 42, 'john.doe', 1700000000, ['LEAD_READ', 'CONTACT_WRITE']);
        $this->assertInstanceOf(UserInterface::class, $user);
    }

    public function testGettersReturnExpectedValues(): void
    {
        $user = new User(10, 42, 'john.doe', 1700000000, ['LEAD_READ', 'CONTACT_WRITE']);

        $this->assertSame(42, $user->getId());
        $this->assertSame(10, $user->getAccountId());
        $this->assertSame('john.doe', $user->getUserIdentifier());
        $this->assertSame(1700000000, $user->getTokenIssuedAt());
        $this->assertSame(['LEAD_READ', 'CONTACT_WRITE'], $user->getPermissions());
        $this->assertSame([], $user->getRoles()); // roles are empty by design
    }

    public function testEraseCredentialsDoesNothing(): void
    {
        $user = new User(1, 1, 'foo', 1, []);
        $this->assertNull($user->eraseCredentials());
    }
}
