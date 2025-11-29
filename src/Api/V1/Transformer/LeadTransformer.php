<?php

declare(strict_types=1);

namespace App\Api\V1\Transformer;

use App\CRM\Lead\Entity\Lead;
use App\CRM\Lead\Enum\IncludesEnum;

readonly class LeadTransformer
{
    public function __construct(
        private LeadCoreTransformer $leadCoreTransformer,
        private ContactCoreTransformer $contactCoreTransformer,
    ) {
    }

    /**
     * GET /leads -- get all leads.
     * Response must be paginated, so we have to return an array with meta.
     *
     * @param array $leads
     * @param int $limit
     * @param array $with
     *
     * @return array
     */
    public function transformCollection(array $leads, int $limit, array $with = []): array
    {
        $items = array_map(
            fn (Lead $lead) => $this->transform($lead, $with),
            $leads
        );

        $nextAfterId = !empty($leads)
            ? end($leads)->getId()
            : null;

        return [
            '_meta' => [
                'limit' => $limit,
                'next_after_id' => $nextAfterId,
            ],
            'leads' => $items,
        ];
    }

    /**
     * @param Lead $lead
     * @param array $with
     *
     * @return array
     */
    public function transform(Lead $lead, array $with = []): array
    {
        $data = $this->leadCoreTransformer->transform($lead);

        if (in_array(IncludesEnum::CONTACTS->value, $with, true)) {
            $data['_embedded'][IncludesEnum::CONTACTS->value] = $this->contactCoreTransformer->transformCollection(
                $lead->getContacts()->toArray()
            );
        }

        return $data;
    }
}
