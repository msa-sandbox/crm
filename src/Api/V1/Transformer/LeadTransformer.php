<?php

declare(strict_types=1);

namespace App\Api\V1\Transformer;

use App\CRM\Lead\Entity\Lead;

class LeadTransformer
{
    public function __construct()
    {
    }

    /**
     * For requests like POST /leads.
     * In this case we need to return only new lead IDs.
     *
     * @param Lead[] $data
     *
     * @return array
     */
    public function transformCreateLeads(array $data): array
    {
        return array_map(fn (Lead $lead) => ['id' => $lead->getId()], $data);
    }
}
