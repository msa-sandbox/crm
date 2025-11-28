<?php

declare(strict_types=1);

namespace App\Api\V1\Handler\Contact;

use App\Api\V1\Dto\Request\Contact\CreateContactCollection;
use App\Api\V1\Factory\ContactFactory;
use App\Api\V1\Transformer\ContactTransformer;
use App\CRM\Contact\Contract\CreateContactInterface;
use App\Security\Enum\PermissionActionEnum;
use App\Security\Enum\PermissionEntityEnum;
use App\Security\PermissionChecker;
use App\Security\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

readonly class CreateContactHandler
{
    public function __construct(
        private Security $security,
        private PermissionChecker $permissionChecker,
        private ContactFactory $contactFactory,
        private CreateContactInterface $createContact,
        private ContactTransformer $contactTransformer,
    ) {
    }

    /**
     * We do not have any unique logic, so create all contacts.
     *
     * @param CreateContactCollection $dtos
     *
     * @return array
     */
    public function createBulk(CreateContactCollection $dtos): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $this->assertNoDuplicateEmails($dtos->all());

        $this->permissionChecker->assertGranted($user, PermissionEntityEnum::CONTACT, PermissionActionEnum::WRITE);

        $contacts = [];
        foreach ($dtos->all() as $dto) {
            $contacts[] = $this->contactFactory->fromDto($dto, $user->getAccountId(), $user->getId());
        }

        $created = $this->createContact->createContacts($contacts, $user->getAccountId());

        return $this->contactTransformer->transformCreateContacts($created);
    }

    /**
     * We do not allow duplicate emails since we mark contacts as unique by email.
     *
     * @param array $dtos
     *
     * @return void
     */
    private function assertNoDuplicateEmails(array $dtos): void
    {
        $emails = [];
        foreach ($dtos as $dto) {
            if ($dto->getEmail()) {
                $email = strtolower($dto->getEmail());
                if (isset($emails[$email])) {
                    throw new InvalidArgumentException(
                        sprintf('Duplicate email in request: %s', $email)
                    );
                }
                $emails[$email] = true;
            }
        }
    }
}
