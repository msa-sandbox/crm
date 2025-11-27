<?php

namespace App\CRM\Contact\Contract;

use App\CRM\Contact\Entity\Contact;

interface CreateContactInterface
{
    /**
     * @param Contact[] $data
     *
     * @return Contact[]
     */
    public function createContacts(array $data, int $accountId): array;
}
