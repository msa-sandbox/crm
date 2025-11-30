<?php

declare(strict_types=1);

namespace App\Infrastructure\Redis;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

/**
 * Кеш для хранения информации об инвалидированных пользователях.
 *
 * Ключи: invalidated_user_{id}
 * Namespace в Redis: auth_cache
 */
final class AuthCache extends Psr16Cache
{
    public function __construct(string $redisDsn)
    {
        $connection = RedisAdapter::createConnection($redisDsn);

        // RedisAdapter(namespace, default_lifetime)
        $adapter = new RedisAdapter(
            $connection,
            'auth_cache',   // For custom and readable namespace
            86400 + 3600 // TTL: 24h + 1h buffer
        );

        parent::__construct($adapter);
    }
}
