<?php

namespace App\CRM\Lead\Contract;

use App\CRM\Lead\Entity\Lead;
use App\CRM\Lead\ValueObject\LeadSearchCriteria;
use Doctrine\Persistence\ObjectRepository;

interface LeadRepositoryInterface extends ObjectRepository
{
    public function add(Lead $lead): void;

    public function remove(Lead $lead): void;

    public function flush(): void;

    public function findActiveById(int $id): ?Lead;

    public function findByAccountId(LeadSearchCriteria $criteria): array;

    /**
     * @param int $id
     * @param int $accountId
     * @param bool $withContacts
     *
     * @return Lead|null
     */
    public function findOneById(int $id, int $accountId, bool $withContacts): ?Lead;
}
