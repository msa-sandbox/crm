<?php

declare(strict_types=1);

namespace App\Service;

use App\Helper\ExceptionFormatter;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class ExceptionLogger
{
    public function __construct(
        private LoggerInterface $logger,
        private ExceptionFormatter $formatter,
    ) {
    }

    /**
     * @param Throwable $exception
     * @param string $level
     * @param array $context
     *
     * @return void
     */
    public function log(Throwable $exception, string $level = 'error', array $context = []): void
    {
        $data = array_merge(
            $context,
            $this->formatter->format($exception)
        );

        $this->logger->log($level, $exception->getMessage(), $data);
    }

    /**
     * @param Throwable $exception
     * @param array $context
     *
     * @return void
     */
    public function error(Throwable $exception, array $context = []): void
    {
        $this->log($exception, 'error', $context);
    }

    /**
     * @param Throwable $exception
     * @param array $context
     *
     * @return void
     */
    public function warning(Throwable $exception, array $context = []): void
    {
        $this->log($exception, 'warning', $context);
    }

    /**
     * @param Throwable $exception
     * @param array $context
     *
     * @return void
     */
    public function critical(Throwable $exception, array $context = []): void
    {
        $this->log($exception, 'critical', $context);
    }
}
