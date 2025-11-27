<?php

namespace App\CRM\Lead\Contract;

use App\CRM\Lead\Entity\Lead;
use Doctrine\Persistence\ObjectRepository;

interface LeadRepositoryInterface extends ObjectRepository
{
    public function add(Lead $lead): void;

    public function remove(Lead $lead): void;

    public function flush(): void;

    public function findActiveById(int $id): ?Lead;

    public function findByAccountId(int $accountId, bool $includeDeleted = false): array;
}
