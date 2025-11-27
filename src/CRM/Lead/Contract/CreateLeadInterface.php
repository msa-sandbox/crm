<?php

namespace App\CRM\Lead\Contract;

use App\CRM\Lead\Entity\Lead;

interface CreateLeadInterface
{
    /**
     * Create many new entities.
     *
     * @param Lead[] $data
     *
     * @return Lead[]
     */
    public function createLeads(array $data): array;
}
