<?php

declare(strict_types=1);

namespace App\Api\V1\Transformer;

use App\CRM\Contact\Entity\Contact;
use App\CRM\Contact\Enum\IncludesEnum;

readonly class ContactTransformer
{
    public function __construct(
        private ContactCoreTransformer $contactCoreTransformer,
        private LeadCoreTransformer $leadCoreTransformer,
    ) {
    }

    /**
     * GET /contacts -- get all contacts.
     * Response must be paginated, so we have to return an array with meta.
     *
     * @param array $contacts
     * @param int $limit
     * @param array $with
     *
     * @return array
     */
    public function transformCollection(array $contacts, int $limit, array $with = []): array
    {
        $items = array_map(
            fn (Contact $contact) => $this->transform($contact, $with),
            $contacts
        );

        $nextAfterId = !empty($contacts)
            ? end($contacts)->getId()
            : null;

        return [
            '_meta' => [
                'limit' => $limit,
                'next_after_id' => $nextAfterId,
            ],
            'contacts' => $items,
        ];
    }

    /**
     * @param Contact $contact
     * @param array $with
     *
     * @return array
     */
    public function transform(Contact $contact, array $with = []): array
    {
        $data = $this->contactCoreTransformer->transform($contact);

        if (in_array(IncludesEnum::LEADS->value, $with, true)) {
            $data['_embedded'][IncludesEnum::LEADS->value] = $this->leadCoreTransformer->transformCollection(
                $contact->getLeads()->toArray()
            );
        }

        return $data;
    }
}
