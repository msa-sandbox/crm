<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Helper\ExceptionFormatter;
use App\Service\ExceptionLogger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class ExceptionLoggerTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private ExceptionFormatter&MockObject $formatter;
    private ExceptionLogger $exceptionLogger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->formatter = $this->createMock(ExceptionFormatter::class);
        $this->exceptionLogger = new ExceptionLogger($this->logger, $this->formatter);
    }

    public function testLogCallsLoggerWithFormattedData(): void
    {
        $exception = new RuntimeException('DB error', 500);
        $formatted = ['type' => RuntimeException::class, 'extra' => 'value'];

        $this->formatter
            ->expects($this->once())
            ->method('format')
            ->with($exception)
            ->willReturn($formatted);

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(
                'error',
                'DB error',
                $this->callback(function (array $context) {
                    // Check that all fields from formatted data are present in context
                    return isset($context['extra']) && 'value' === $context['extra'];
                })
            );

        $this->exceptionLogger->log($exception);
    }

    public function testErrorCallsLogWithErrorLevel(): void
    {
        $exception = new RuntimeException('boom');
        $this->formatter->method('format')->willReturn([]);

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with('error', 'boom', []);

        $this->exceptionLogger->error($exception);
    }

    public function testWarningCallsLogWithWarningLevel(): void
    {
        $exception = new RuntimeException('warn');
        $this->formatter->method('format')->willReturn([]);

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with('warning', 'warn', []);

        $this->exceptionLogger->warning($exception);
    }

    public function testCriticalCallsLogWithCriticalLevel(): void
    {
        $exception = new RuntimeException('critical');
        $this->formatter->method('format')->willReturn([]);

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with('critical', 'critical', []);

        $this->exceptionLogger->critical($exception);
    }

    public function testContextIsMergedWithFormattedData(): void
    {
        $exception = new RuntimeException('Merged context');
        $formatted = ['file' => 'some.php'];
        $context = ['request_id' => 'abc123'];

        $this->formatter->method('format')->willReturn($formatted);

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(
                'error',
                'Merged context',
                $this->callback(function ($ctx) {
                    return isset($ctx['request_id'], $ctx['file']);
                })
            );

        $this->exceptionLogger->log($exception, 'error', $context);
    }
}
