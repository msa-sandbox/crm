<?php

namespace App\CRM\Contact\Contract;

use App\CRM\Contact\Entity\Contact;
use App\CRM\Contact\ValueObject\ContactSearchCriteria;

interface GetContactInterface
{
    /**
     * @return Contact[]
     */
    public function getContactsByAccount(ContactSearchCriteria $criteria): array;

    /**
     * Get contact by id. Check for a correct account id.
     *
     * @param int $id
     * @param int $accountId
     * @param array $includes
     *
     * @return Contact|null
     */
    public function getContactById(int $id, int $accountId, array $includes): ?Contact;
}
