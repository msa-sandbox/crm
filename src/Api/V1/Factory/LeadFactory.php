<?php

declare(strict_types=1);

namespace App\Api\V1\Factory;

use App\Api\V1\Dto\Request\Lead\CreateLeadDto;
use App\CRM\Lead\Entity\Lead;

class LeadFactory
{
    public function fromDto(CreateLeadDto $dto, int $userId): Lead
    {
        return new Lead(
            title: $dto->getTitle(),
            status: $dto->getStatus(),
            pipelineStage: $dto->getPipelineStage(),
            budget: $dto->getBudget(),
            userId: $userId,
            createdBy: $userId,
            updatedBy: $userId,
            description: $dto->getDescription(),
            notes: $dto->getNotes(),
        );
    }
}
