<?php

declare(strict_types=1);

namespace App\Api\V1\Handler\Contact;

use App\Api\V1\Dto\Request\Contact\GetContactItemQueryDto;
use App\Api\V1\Dto\Request\Contact\GetContactQueryDto;
use App\Api\V1\Transformer\ContactTransformer;
use App\CRM\Contact\Contract\GetContactInterface;
use App\CRM\Contact\ValueObject\ContactSearchCriteria;
use App\Security\Enum\PermissionActionEnum;
use App\Security\Enum\PermissionEntityEnum;
use App\Security\PermissionChecker;
use App\Security\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class GetContactHandler
{
    public function __construct(
        private Security $security,
        private PermissionChecker $permissionChecker,
        private GetContactInterface $getContact,
        private ContactTransformer $transformer,
    ) {
    }

    /**
     * @param GetContactQueryDto $queryDto
     *
     * @return array
     */
    public function getList(GetContactQueryDto $queryDto): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $this->permissionChecker->assertGranted($user, PermissionEntityEnum::CONTACT, PermissionActionEnum::READ);

        $criteria = new ContactSearchCriteria(
            accountId: $user->getAccountId(),
            limit: $queryDto->getLimit(),
            afterId: $queryDto->getAfterId(),
            search: $queryDto->getSearch(),
            includeDeleted: $queryDto->includeDeleted(),
            includes: $queryDto->getIncludes(),
        );

        $contacts = $this->getContact->getContactsByAccount($criteria);

        return $this->transformer->transformCollection($contacts, $queryDto->getLimit(), $queryDto->getIncludes());
    }

    /**
     * @param int $id
     * @param GetContactItemQueryDto $queryDto
     *
     * @return array
     */
    public function getOneById(int $id, GetContactItemQueryDto $queryDto): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $this->permissionChecker->assertGranted($user, PermissionEntityEnum::CONTACT, PermissionActionEnum::READ);

        $contact = $this->getContact->getContactById(
            $id,
            $user->getAccountId(),
            $queryDto->getIncludes(),
        );

        if (!$contact) {
            throw new NotFoundHttpException(sprintf('Contact %d not found', $id));
        }

        return $this->transformer->transform($contact, $queryDto->getIncludes());
    }
}
