<?php

declare(strict_types=1);

namespace App\CRM\Lead\Service\Query;

use App\CRM\Lead\Contract\GetLeadInterface;
use App\CRM\Lead\Contract\LeadRepositoryInterface;
use App\CRM\Lead\Entity\Lead;
use App\CRM\Lead\Enum\RelationsEnum;
use App\CRM\Lead\ValueObject\LeadSearchCriteria;

readonly class GetLeadService implements GetLeadInterface
{
    public function __construct(
        private LeadRepositoryInterface $repository,
    ) {
    }

    /**
     * @internal
     */
    public function getLeadsByAccount(LeadSearchCriteria $criteria): array
    {
        return $this->repository->findByAccountId($criteria);
    }

    /**
     * @internal
     */
    public function getLeadById(int $id, int $accountId, array $with): ?Lead
    {
        $withContacts = in_array(RelationsEnum::CONTACTS->value, $with, true);

        return $this->repository->findOneById($id, $accountId, $withContacts);
    }
}
