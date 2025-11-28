<?php

declare(strict_types=1);

namespace App\Api\V1\Handler\Lead;

use App\Api\V1\Dto\Request\Lead\CreateLeadCollectionDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadWithContactCollectionDto;
use App\Api\V1\Factory\LeadFactory;
use App\Api\V1\Transformer\LeadTransformer;
use App\CRM\Contact\Contract\CreateContactInterface;
use App\CRM\Lead\Contract\CreateLeadInterface;
use App\Security\Enum\PermissionActionEnum;
use App\Security\Enum\PermissionEntityEnum;
use App\Security\PermissionChecker;
use App\Security\User;
use App\Service\TransactionManager;
use Symfony\Bundle\SecurityBundle\Security;
use Throwable;

readonly class CreateLeadHandler
{
    public function __construct(
        private Security $security,
        private PermissionChecker $permissionChecker,
        private LeadFactory $leadFactory,
        private TransactionManager $transactionManager,
        private CreateLeadInterface $createLead,
        private CreateContactInterface $createContact,
        private LeadTransformer $leadTransformer,
    ) {
    }

    /**
     * We do not have any unique logic, so create all leads.
     *
     * @param CreateLeadCollectionDto $dtos
     *
     * @return array
     *
     * @throws Throwable
     */
    public function createBulk(CreateLeadCollectionDto $dtos): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $this->permissionChecker->assertGranted($user, PermissionEntityEnum::LEAD, PermissionActionEnum::WRITE);

        return $this->transactionManager->execute(function () use ($dtos, $user) {
            $leads = [];
            foreach ($dtos->all() as $dto) {
                $leads[] = $this->leadFactory->fromDto($dto, $user->getAccountId(), $user->getId());
            }

            $created = $this->createLead->createLeads($leads);

            return $this->leadTransformer->transformCreateLeads($created);
        });
    }

    /**
     * @param CreateLeadWithContactCollectionDto $dtos
     *
     * @return array
     *
     * @throws Throwable
     */
    public function createBulkWithContacts(CreateLeadWithContactCollectionDto $dtos): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $this->permissionChecker->assertGranted($user, PermissionEntityEnum::LEAD, PermissionActionEnum::WRITE);
        $this->permissionChecker->assertGranted($user, PermissionEntityEnum::CONTACT, PermissionActionEnum::WRITE);

        return $this->transactionManager->execute(function () use ($dtos, $user) {
            $accountId = $user->getAccountId();
            $userId = $user->getId();

            $leads = [];
            $allContacts = [];

            // Build leads and embedded contacts
            foreach ($dtos->all() as $leadDto) {
                $lead = $this->leadFactory->fromDtoWithContacts($leadDto, $accountId, $userId);

                foreach ($lead->getContacts() as $contact) {
                    $allContacts[] = $contact;
                }

                $leads[] = $lead;
            }

            // Create/update contacts
            $createdContacts = $this->createContact->createContacts($allContacts, $accountId);

            // Index contacts by email
            $contactMap = [];
            foreach ($createdContacts as $contact) {
                if ($contact->getEmail()) {
                    $contactMap[strtolower($contact->getEmail())] = $contact;
                }
            }

            // Bind contacts to leads
            foreach ($leads as $lead) {
                // Save original contacts to get their emails (unique index)
                $originalContacts = $lead->getContacts()->toArray();
                $lead->clearContacts();

                foreach ($originalContacts as $contact) {
                    $email = strtolower((string) $contact->getEmail());
                    if ($email && isset($contactMap[$email])) {
                        $lead->addContact($contactMap[$email]);
                    }
                }
            }

            // Create leads with embedded contacts
            $createdLeads = $this->createLead->createLeads($leads);

            return $this->leadTransformer->transformCreateLeadsWithContacts($createdLeads);
        });
    }
}
