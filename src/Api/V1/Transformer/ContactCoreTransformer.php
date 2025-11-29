<?php

declare(strict_types=1);

namespace App\Api\V1\Transformer;

use App\CRM\Contact\Entity\Contact;

readonly class ContactCoreTransformer
{
    /**
     * For requests like POST /contacts.
     * In this case we need to return only new (or found) contact IDs.
     *
     * @param Contact[] $data
     *
     * @return array
     */
    public function transformCreateContacts(array $data): array
    {
        return array_map(fn (Contact $contact) => ['id' => $contact->getId()], $data);
    }

    /**
     * @param Contact[] $contacts
     *
     * @return array
     */
    public function transformCollection(array $contacts): array
    {
        return array_map([$this, 'transform'], $contacts);
    }

    /**
     * @param Contact $contact
     *
     * @return array
     */
    public function transform(Contact $contact): array
    {
        return [
            'id' => $contact->getId(),
            'firstName' => $contact->getFirstName(),
            'lastName' => $contact->getLastName(),
            'email' => $contact->getEmail(),
            'phone' => $contact->getPhone(),
            'company' => $contact->getCompany(),
            'city' => $contact->getCity(),
            'country' => $contact->getCountry(),
            'notes' => $contact->getNotes(),
            'isDeleted' => $contact->isDeleted(),
            'createdAt' => $contact->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $contact->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
