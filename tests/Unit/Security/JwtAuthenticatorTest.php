<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\JwtAuthenticator;
use App\Security\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Main point is check that user will be set within a symfony passport
 *  and that we will be able to get it from it.
 * I am not interested in testing JWTManager and LoggerInterface here.
 */
final class JwtAuthenticatorTest extends TestCase
{
    private JWTTokenManagerInterface&MockObject $jwtManager;
    private LoggerInterface&MockObject $logger;
    private JwtAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->authenticator = new JwtAuthenticator($this->jwtManager, $this->logger);
    }

    public function testAuthenticateCreatesExpectedUser(): void
    {
        $data = [
            'user_id' => 42,
            'username' => 'john.doe',
            'permissions' => ['LEAD_READ', 'CONTACT_WRITE'],
            'iat' => 1700000000,
        ];

        $this->jwtManager
            ->expects($this->once())
            ->method('parse')
            ->with('valid_token')
            ->willReturn($data);

        $request = new Request();
        $request->headers->set('Authorization', 'Bearer valid_token');

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);

        $userBadge = $passport->getBadge(UserBadge::class);
        $this->assertInstanceOf(UserBadge::class, $userBadge);

        /** @var User $user */
        $user = ($userBadge->getUserLoader())($data['username']);
        $this->assertInstanceOf(User::class, $user);

        $this->assertSame(42, $user->getId());
        $this->assertSame('john.doe', $user->getUserIdentifier());
        $this->assertSame(['LEAD_READ', 'CONTACT_WRITE'], $user->getPermissions());
        $this->assertSame(1, $user->getAccountId()); // fixed account
    }

    public function testAuthenticateThrowsIfTokenInvalid(): void
    {
        $this->jwtManager
            ->expects($this->once())
            ->method('parse')
            ->willThrowException(new RuntimeException('bad token'));

        $request = new Request();
        $request->headers->set('Authorization', 'Bearer bad_token');

        $this->logger->expects($this->once())->method('warning');

        $this->expectException(AuthenticationException::class);
        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsIfTokenMissingRequiredFields(): void
    {
        $this->jwtManager
            ->expects($this->once())
            ->method('parse')
            ->willReturn(['username' => 'john']); // missing fields

        $request = new Request();
        $request->headers->set('Authorization', 'Bearer token');

        $this->logger->expects($this->once())->method('warning');

        $this->expectException(AuthenticationException::class);
        $this->authenticator->authenticate($request);
    }
}
