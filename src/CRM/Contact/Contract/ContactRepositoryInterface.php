<?php

namespace App\CRM\Contact\Contract;

use App\CRM\Contact\Entity\Contact;
use Doctrine\Persistence\ObjectRepository;

interface ContactRepositoryInterface extends ObjectRepository
{
    public function add(Contact $lead): void;

    public function remove(Contact $lead): void;

    public function flush(): void;

    public function findActiveById(int $id): ?Contact;

    /**
     * @param array $emails
     * @param int $accountId
     * @param bool $includeDeleted
     *
     * @return Contact[]
     */
    public function findExistingByEmailsAndAccount(array $emails, int $accountId, bool $includeDeleted = false): array;
}
