<?php

declare(strict_types=1);

namespace App\Api\V1\Handler\Lead;

use App\Api\V1\Dto\Request\Lead\GetLeadItemQueryDto;
use App\Api\V1\Dto\Request\Lead\GetLeadQueryDto;
use App\Api\V1\Transformer\LeadTransformer;
use App\CRM\Lead\Contract\GetLeadInterface;
use App\CRM\Lead\Enum\RelationsEnum;
use App\CRM\Lead\ValueObject\LeadSearchCriteria;
use App\Security\Enum\PermissionActionEnum;
use App\Security\Enum\PermissionEntityEnum;
use App\Security\PermissionChecker;
use App\Security\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class GetLeadHandler
{
    public function __construct(
        private Security $security,
        private PermissionChecker $permissionChecker,
        private GetLeadInterface $getLead,
        private LeadTransformer $transformer,
    ) {
    }

    /**
     * @param GetLeadQueryDto $queryDto
     *
     * @return array
     */
    public function getList(GetLeadQueryDto $queryDto): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        // Check permissions
        $this->permissionChecker->assertGranted($user, PermissionEntityEnum::LEAD, PermissionActionEnum::READ);
        foreach ($queryDto->getWith() as $with) {
            match ($with) {
                RelationsEnum::CONTACTS->value => $this->permissionChecker->assertGranted($user, PermissionEntityEnum::CONTACT, PermissionActionEnum::READ),
                default => null,
            };
        }

        $criteria = new LeadSearchCriteria(
            accountId: $user->getAccountId(),
            limit: $queryDto->getLimit(),
            afterId: $queryDto->getAfterId(),
            search: $queryDto->getSearch(),
            includeDeleted: $queryDto->includeDeleted(),
            with: $queryDto->getWith(),
        );

        $Leads = $this->getLead->getLeadsByAccount($criteria);

        return $this->transformer->transformCollection($Leads, $queryDto->getLimit(), $queryDto->getWith());
    }

    /**
     * @param int $id
     * @param GetLeadItemQueryDto $queryDto
     *
     * @return array
     */
    public function getOneById(int $id, GetLeadItemQueryDto $queryDto): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $this->permissionChecker->assertGranted($user, PermissionEntityEnum::LEAD, PermissionActionEnum::READ);

        $lead = $this->getLead->getLeadById(
            $id,
            $user->getAccountId(),
            $queryDto->getWith(),
        );

        if (!$lead) {
            throw new NotFoundHttpException(sprintf('LEad %d not found', $id));
        }

        return $this->transformer->transform($lead, $queryDto->getWith());
    }
}
