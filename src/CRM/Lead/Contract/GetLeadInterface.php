<?php

namespace App\CRM\Lead\Contract;

use App\CRM\Lead\Entity\Lead;
use App\CRM\Lead\ValueObject\LeadSearchCriteria;

interface GetLeadInterface
{
    /**
     * @return Lead[]
     */
    public function getLeadsByAccount(LeadSearchCriteria $criteria): array;

    /**
     * Get lead by id. Check for a correct account id.
     *
     * @param int $id
     * @param int $accountId
     * @param array $with
     *
     * @return Lead|null
     */
    public function getLeadById(int $id, int $accountId, array $with): ?Lead;
}
