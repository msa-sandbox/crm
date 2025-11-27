<?php

declare(strict_types=1);

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Throwable;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(Request $request): bool
    {
        $header = $request->headers->get('Authorization', '');

        // Check if token exists
        return str_starts_with($header, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        // Get token
        $authHeader = $request->headers->get('Authorization');
        $jwt = substr($authHeader, 7);

        // Validate & get payload
        try {
            $data = $this->jwtManager->parse($jwt);
        } catch (Throwable $e) {
            $this->logger->warning('JWT token validation failed', [
                'error' => $e->getMessage(),
            ]);

            throw new AuthenticationException('Invalid JWT token');
        }

        // Ensure required fields exist in token
        if (!isset($data['user_id']) || !isset($data['username']) || !isset($data['permissions']) || !is_array($data['permissions'])) {
            $this->logger->warning('JWT token missing required fields', [
                'has_user_id' => isset($data['user_id']),
                'has_username' => isset($data['username']),
                'has_permissions' => isset($data['permissions']),
            ]);

            throw new AuthenticationException('Invalid JWT token: required fields not found');
        }

        return new SelfValidatingPassport(
            new UserBadge($data['username'], function () use ($data) {
                return new User(
                    1,  // Currently, we do not support multiple accounts
                    $data['user_id'],
                    $data['username'],
                    $data['iat'],
                    $data['permissions'],
                );
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Nothing, currently
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->info('Authentication attempt failed', [
            'reason' => $exception->getMessage(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
        ]);

        return new JsonResponse(['error' => 'Invalid or expired JWT token'], Response::HTTP_UNAUTHORIZED);
    }
}
