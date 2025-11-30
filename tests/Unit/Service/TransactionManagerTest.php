<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\TransactionException;
use App\Service\ExceptionLogger;
use App\Service\TransactionManager;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class TransactionManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ExceptionLogger&MockObject $logger;
    private TransactionManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(ExceptionLogger::class);
        $this->manager = new TransactionManager($this->em, $this->logger);
    }

    public function testExecuteCommitsTransactionOnSuccess(): void
    {
        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('commit');
        $this->em->expects($this->never())->method('rollback');

        $callback = fn () => 'OK';

        $result = $this->manager->execute($callback);

        $this->assertSame('OK', $result);
    }

    public function testExecuteRollsBackAndThrowsTransactionExceptionOnException(): void
    {
        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->never())->method('commit');
        $this->em->expects($this->once())->method('rollback');
        $this->logger->expects($this->once())->method('error');

        $callback = function () {
            throw new RuntimeException('db error');
        };

        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('Transaction failed');

        $this->manager->execute($callback);
    }

    public function testExecuteRollsBackAndRethrowsThrowable(): void
    {
        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->never())->method('commit');
        $this->em->expects($this->once())->method('rollback');
        $this->logger->expects($this->once())->method('error');

        $fatal = new class('fatal') extends Error {};

        $callback = fn () => throw $fatal;

        try {
            $this->manager->execute($callback);
            $this->fail('Expected Throwable not thrown');
        } catch (Throwable $e) {
            $this->assertSame($fatal, $e);
        }
    }
}
