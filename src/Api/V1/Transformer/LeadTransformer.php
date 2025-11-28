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

    /**
     * For requests to POST /leads/complex.
     *
     * @param array $data
     *
     * @return array
     * [
     *  {
     *    "id": 89,
     *    "contacts": [
     *      { "id": 20 },
     *      { "id": 21 }
     *    ]
     *  }
     * ]
     */
    public function transformCreateLeadsWithContacts(array $data): array
    {
        return array_map(function (Lead $lead) {
            return [
                'id' => $lead->getId(),
                'contacts' => array_map(
                    fn ($contact) => ['id' => $contact->getId()],
                    $lead->getContacts()->toArray()
                ),
            ];
        }, $data);
    }
}
