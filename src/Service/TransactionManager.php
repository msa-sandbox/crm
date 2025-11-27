<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\TransactionException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Throwable;

readonly class TransactionManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExceptionLogger $logger,
    ) {
    }

    /**
     * @param callable $callback
     *
     * @return mixed
     *
     * @throws Throwable
     */
    public function execute(callable $callback): mixed
    {
        $this->entityManager->beginTransaction();

        try {
            $result = $callback();

            $this->entityManager->commit();

            return $result;
        } catch (Exception $exception) {
            // All errors from the transaction should be here

            $this->entityManager->rollback();

            $this->logger->error($exception);

            throw new TransactionException('Transaction failed', 0, $exception);
        } catch (Throwable $exception) {
            // In case of some fatal error

            $this->entityManager->rollback();

            $this->logger->error($exception);

            throw $exception;
        }
    }
}
