<?php

declare(strict_types=1);

namespace App\CRM\Contact\Service\Query;

use App\CRM\Contact\Contract\ContactRepositoryInterface;
use App\CRM\Contact\Contract\GetContactInterface;
use App\CRM\Contact\Entity\Contact;
use App\CRM\Contact\Enum\RelationsEnum;
use App\CRM\Contact\ValueObject\ContactSearchCriteria;

readonly class GetContactService implements GetContactInterface
{
    public function __construct(
        private ContactRepositoryInterface $repository,
    ) {
    }

    /**
     * @internal
     */
    public function getContactsByAccount(ContactSearchCriteria $criteria): array
    {
        return $this->repository->findByAccountId($criteria);
    }

    /**
     * @internal
     */
    public function getContactById(int $id, int $accountId, array $with): ?Contact
    {
        $withLeads = in_array(RelationsEnum::LEADS->value, $with, true);

        return $this->repository->findOneById($id, $accountId, $withLeads);
    }
}
