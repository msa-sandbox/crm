<?php

declare(strict_types=1);

namespace App\Api\V1\Transformer;

use App\CRM\Contact\Entity\Contact;

class ContactTransformer
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
}
