<?php

declare(strict_types=1);

namespace App\CRM\Lead\Service\Command;

use App\CRM\Lead\Contract\CreateLeadInterface;
use App\CRM\Lead\Contract\LeadRepositoryInterface;
use App\CRM\Lead\Entity\Lead;
use Throwable;

readonly class CreateLeadService implements CreateLeadInterface
{
    public function __construct(
        private LeadRepositoryInterface $leadRepository,
    ) {
    }

    /**
     * Create many new entities.
     *
     * @param Lead[] $data
     *
     * @return array
     *
     * @throws Throwable
     */
    public function createLeads(array $data): array
    {
        foreach ($data as $lead) {
            $this->leadRepository->add($lead);
        }

        $this->leadRepository->flush();

        return $data;
    }
}
