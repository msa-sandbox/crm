<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\Enum\PermissionActionEnum;
use App\Security\Enum\PermissionEntityEnum;
use App\Security\PermissionChecker;
use App\Security\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class PermissionCheckerTest extends TestCase
{
    private PermissionChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new PermissionChecker();
    }

    public function testIsGrantedReturnsTrueWhenPermissionExists(): void
    {
        $user = new User(
            accountId: 1,
            id: 10,
            username: 'john',
            tokenIssuedAt: time(),
            permissions: [
                'lead' => ['read', 'write'],
                'contact' => ['read'],
            ]
        );

        $this->assertTrue($this->checker->isGranted(
            $user,
            PermissionEntityEnum::LEAD,
            PermissionActionEnum::WRITE
        ));
    }

    public function testIsGrantedReturnsFalseWhenEntityMissing(): void
    {
        $user = new User(1, 10, 'john', time(), []);

        $this->assertFalse($this->checker->isGranted(
            $user,
            PermissionEntityEnum::CONTACT,
            PermissionActionEnum::READ
        ));
    }

    public function testIsGrantedReturnsFalseWhenActionMissing(): void
    {
        $user = new User(
            1,
            10,
            'john',
            time(),
            ['lead' => ['read']]
        );

        $this->assertFalse($this->checker->isGranted(
            $user,
            PermissionEntityEnum::LEAD,
            PermissionActionEnum::DELETE
        ));
    }

    public function testAssertGrantedDoesNotThrowIfGranted(): void
    {
        $user = new User(1, 10, 'john', time(), ['contact' => ['read']]);

        $this->checker->assertGranted(
            $user,
            PermissionEntityEnum::CONTACT,
            PermissionActionEnum::READ
        );

        $this->addToAssertionCount(1); // no exception -> ok
    }

    public function testAssertGrantedThrowsWhenNotGranted(): void
    {
        $user = new User(1, 10, 'john', time(), []);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('User does not have permission "lead:read"');

        $this->checker->assertGranted(
            $user,
            PermissionEntityEnum::LEAD,
            PermissionActionEnum::READ
        );
    }
}
