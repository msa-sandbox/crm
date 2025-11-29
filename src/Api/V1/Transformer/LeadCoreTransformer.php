<?php

declare(strict_types=1);

namespace App\Api\V1\Transformer;

use App\CRM\Lead\Entity\Lead;

class LeadCoreTransformer
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

    /**
     * @param Lead[] $lead
     *
     * @return array
     */
    public function transformCollection(array $lead): array
    {
        return array_map([$this, 'transform'], $lead);
    }

    /**
     * @param Lead $lead
     *
     * @return array
     */
    public function transform(Lead $lead): array
    {
        return [
            'id' => $lead->getId(),
            'title' => $lead->getTitle(),
            'status' => $lead->getStatus(),
            'pipelineStage' => $lead->getPipelineStage(),
            'budget' => (float) $lead->getBudget(),
            'description' => $lead->getDescription(),
            'notes' => $lead->getNotes(),
            'isDeleted' => $lead->isDeleted(),
            'createdAt' => $lead->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $lead->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
