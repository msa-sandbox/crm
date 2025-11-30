<?php

declare(strict_types=1);

namespace App\Command;

use App\Infrastructure\Redis\AuthCache;
use DateTimeImmutable;
use Redis;
use ReflectionClass;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Displays all invalidated user tokens currently stored in the Redis-based AuthCache. This command is designed for debugging and inspection purposes only.
 *
 * Functionality:
 *  - Connects to the Redis backend used by AuthCache.
 *  - Scans for all keys matching the pattern auth_cache:invalidated_user_*.
 *  - For each key found, it displays:
 *    - The Redis key name
 *    - The invalidation timestamp in a human-readable format
 *    - The remaining TTL (time-to-live) in seconds
 *  - Automatically handles Symfony’s default PHP serialization (values like i:1764517311;).
 */
#[AsCommand(
    name: 'cache:auth:inspect',
    description: 'Lists invalidated user tokens stored in AuthCache (Redis)',
)]
final class InspectAuthCacheCommand extends Command
{
    public function __construct(
        private readonly AuthCache $authCache,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // get pool from a symfony instance
        $ref = new ReflectionClass(\Symfony\Component\Cache\Psr16Cache::class);
        $prop = $ref->getProperty('pool');
        $prop->setAccessible(true);
        $pool = $prop->getValue($this->authCache);

        if ($pool instanceof TraceableAdapter) {
            $pool = $pool->getPool();
        }

        // get real redis instance
        $redis = null;
        if ($pool instanceof RedisAdapter) {
            $ref = new ReflectionClass(RedisAdapter::class);
            $conn = $ref->getProperty('redis');
            $conn->setAccessible(true);
            $redis = $conn->getValue($pool);
        }

        if (!$redis instanceof Redis) {
            $output->writeln('<error>AuthCache backend is not Redis — cannot inspect.</error>');

            return Command::FAILURE;
        }

        // user keys to get all
        $keys = $redis->keys('auth_cache:invalidated_user_*');

        if (empty($keys)) {
            $output->writeln('<info>No invalidation keys found in cache.</info>');

            return Command::SUCCESS;
        }

        $output->writeln('<info>Found '.count($keys).' invalidation key(s):</info>');

        foreach ($keys as $key) {
            $value = $redis->get($key);
            if (false === $value) {
                $output->writeln(sprintf('<comment>%s</comment> → (missing value)', $key));
                continue;
            }

            // Symfony хранит данные в сериализованном виде (например "i:1764517311;")
            $decoded = @unserialize($value);
            $timestamp = is_int($decoded) ? $decoded : (is_numeric($decoded) ? (int) $decoded : null);

            if (null === $timestamp) {
                $output->writeln(sprintf('<comment>%s</comment> → invalid value: %s', $key, $value));
                continue;
            }

            $readable = (new DateTimeImmutable("@$timestamp"))->format('Y-m-d H:i:s');
            $ttl = $redis->ttl($key);

            $output->writeln(sprintf(
                '<comment>%s</comment> → %s (expires in %d sec)',
                $key,
                $readable,
                $ttl
            ));
        }

        return Command::SUCCESS;
    }
}
