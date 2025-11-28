<?php

declare(strict_types=1);

namespace App\Api\V1\Factory;

use App\Api\V1\Dto\Request\Lead\CreateLeadDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadWithContactDto;
use App\CRM\Lead\Entity\Lead;

readonly class LeadFactory
{
    public function __construct(
        private ContactFactory $contactFactory,
    ) {
    }

    public function fromDto(CreateLeadDto|CreateLeadWithContactDto $dto, int $accountId, int $userId): Lead
    {
        return new Lead(
            title: $dto->getTitle(),
            status: $dto->getStatus(),
            pipelineStage: $dto->getPipelineStage(),
            budget: $dto->getBudget(),
            accountId: $accountId,
            userId: $userId,
            createdBy: $userId,
            updatedBy: $userId,
            description: $dto->getDescription(),
            notes: $dto->getNotes(),
        );
    }

    /**
     * @param CreateLeadWithContactDto $dto
     * @param int $accountId
     * @param int $userId
     *
     * @return Lead
     */
    public function fromDtoWithContacts(CreateLeadWithContactDto $dto, int $accountId, int $userId): Lead
    {
        $lead = $this->fromDto($dto, $accountId, $userId);

        foreach ($dto->getEmbedded()->all() as $contactDto) {
            $contact = $this->contactFactory->fromDto($contactDto, $accountId, $userId);
            $lead->addContact($contact);
        }

        return $lead;
    }
}
