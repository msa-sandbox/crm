<?php

declare(strict_types=1);

namespace App\Command;

use App\Infrastructure\Redis\AuthCache;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use RdKafka\Conf;
use RdKafka\Exception;
use RdKafka\KafkaConsumer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

use function json_decode;
use function pcntl_signal;
use function sprintf;
use function strtotime;

/**
 * Consumes Kafka events related to user permission changes or invalidations and updates the AuthCache (Redis) accordingly.
 *
 * Functionality:
 *  - Connects to the configured Kafka brokers and subscribes to the given topic containing user authorization change events.
 *  - Listens for messages in real-time and processes each event payload in JSON format.
 *  - Expects messages of the form:
 *      {
 *          "event": "user.permissions.changed",
 *          "user_id": 3,
 *          "changed_at": "2025-11-30T15:14:59+00:00"
 *      }
 *
 *  - For each valid message:
 *    - Extracts the user ID and timestamp.
 *    - Stores the timestamp in the Redis-based AuthCache under the key pattern invalidated_user_{id}.
 *    - Sets an expiration time (24 hours + 1-hour buffer).
 *  - Supports an optional --replay-24h flag that replays all messages from the beginning of the topic (approx. the last 24 hours).
 *  - Handles graceful shutdown on SIGINT (Ctrl+C) and logs any Kafka or payload errors.
 */
#[AsCommand(
    name: 'kafka:consume:auth',
    description: 'Consumes user permission change/invalidation events and updates AuthCache (Redis)',
)]
final class KafkaConsumeAuthCommand extends Command
{
    public function __construct(
        private readonly string $brokers,
        private readonly string $topicName,
        #[Autowire(service: AuthCache::class)]
        private readonly CacheInterface $authCache,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'replay-24h',
            null,
            InputOption::VALUE_NONE,
            'Replay all messages from the beginning of topic (roughly last 24h).'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        declare(ticks=1);
        $stop = false;
        pcntl_signal(SIGINT, function () use (&$stop, $output): void {
            $output->writeln("\n<info>Stopping consumer...</info>");
            $stop = true;
        });

        $replay = $input->getOption('replay-24h');

        $conf = new Conf();
        $conf->set('metadata.broker.list', $this->brokers);
        $conf->set('group.id', 'crm-auth-consumer');
        $conf->set('auto.offset.reset', 'earliest');
        $conf->set('enable.auto.commit', 'false');

        if ($replay) {
            $conf->setRebalanceCb(function (KafkaConsumer $consumer, $err, ?array $partitions = null) use ($output): void {
                if (RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS === $err) {
                    foreach ($partitions as $partition) {
                        $partition->setOffset(RD_KAFKA_OFFSET_BEGINNING);
                    }
                    $consumer->assign($partitions);
                    $output->writeln('<info>Replaying messages from the beginning (last 24h)</info>');
                } elseif (RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS === $err) {
                    $consumer->assign(null);
                }
            });
        }

        $consumer = new KafkaConsumer($conf);

        try {
            $consumer->subscribe([$this->topicName]);
            $output->writeln("<info>Connected to Kafka: {$this->brokers}</info>");
            $output->writeln("<info>Subscribed to topic: {$this->topicName}</info>");
        } catch (Exception $e) {
            $output->writeln('<error>Kafka subscription failed: '.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        while (!$stop) {
            try {
                $message = $consumer->consume(2000);

                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        $payload = $message->payload;
                        $output->writeln(sprintf('<comment>%s</comment>', $payload));

                        $data = json_decode($payload, true);
                        if (!is_array($data)) {
                            $this->logger->warning('Non-JSON Kafka payload', ['payload' => $payload]);
                            break;
                        }

                        $this->processMessage($data);
                        if (!$replay) {
                            $consumer->commitAsync($message);
                        }
                        break;

                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        break;

                    default:
                        $this->logger->error('Kafka consumer error', [
                            'code' => $message->err,
                            'error' => $message->errstr(),
                        ]);
                        break;
                }
            } catch (Throwable $e) {
                $this->logger->error('Kafka consume loop exception', ['error' => $e->getMessage()]);
            }
        }

        $output->writeln('<info>Consumer stopped gracefully.</info>');

        return Command::SUCCESS;
    }

    private function processMessage(array $data): void
    {
        // expecting: {"event": "user.permissions.changed", "user_id": 3, "changed_at": "2025-11-30T15:14:59+00:00"}
        if (!isset($data['user_id'], $data['changed_at'])) {
            $this->logger->warning('Invalid Kafka message structure', ['payload' => $data]);

            return;
        }

        $userId = (int) $data['user_id'];
        $timestamp = strtotime((string) $data['changed_at']);
        if (false === $timestamp) {
            $this->logger->warning('Invalid timestamp format in Kafka message', ['payload' => $data]);

            return;
        }

        $key = "invalidated_user_{$userId}";
        $ttl = 86400 + 3600; // 24h token TTL + 1h buffer

        // PSR-16: set(key, value, ttl)
        $this->authCache->set($key, $timestamp, $ttl);

        $this->logger->info('Stored user invalidation', [
            'user_id' => $userId,
            'timestamp' => $timestamp,
            'readable' => date('c', $timestamp),
        ]);
    }
}
