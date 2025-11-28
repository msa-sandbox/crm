<?php

declare(strict_types=1);

namespace App\Api\V1\Handler\Lead;

use App\Api\V1\Dto\Request\Lead\CreateLeadCollectionDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadDto;
use App\Api\V1\Factory\LeadFactory;
use App\Api\V1\Transformer\LeadTransformer;
use App\CRM\Lead\Contract\CreateLeadInterface;
use App\Security\Enum\PermissionActionEnum;
use App\Security\Enum\PermissionEntityEnum;
use App\Security\PermissionChecker;
use App\Security\User;
use Symfony\Bundle\SecurityBundle\Security;

readonly class CreateLeadHandler
{
    public function __construct(
        private Security $security,
        private PermissionChecker $permissionChecker,
        private LeadFactory $leadFactory,
        private CreateLeadInterface $createLead,
        private LeadTransformer $leadTransformer,
    ) {
    }

    /**
     * We do not have any unique logic, so create all leads.
     *
     * @param CreateLeadCollectionDto $dtos
     *
     * @return array
     */
    public function createBulk(CreateLeadCollectionDto $dtos): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $this->permissionChecker->assertGranted($user, PermissionEntityEnum::LEAD, PermissionActionEnum::WRITE);

        $leads = [];
        foreach ($dtos->all() as $dto) {
            $leads[] = $this->leadFactory->fromDto($dto, $user->getAccountId(), $user->getId());
        }

        $created = $this->createLead->createLeads($leads);

        return $this->leadTransformer->transformCreateLeads($created);
    }
}
