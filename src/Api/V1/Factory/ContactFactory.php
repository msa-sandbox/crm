<?php

declare(strict_types=1);

namespace App\Api\V1\Factory;

use App\Api\V1\Dto\Request\Contact\CreateContactDto;
use App\CRM\Contact\Entity\Contact;

class ContactFactory
{
    public function fromDto(CreateContactDto $dto, int $accountId, int $userId): Contact
    {
        return new Contact(
            firstName: $dto->getFirstName(),
            lastName: $dto->getLastName(),
            accountId: $accountId,
            userId: $userId,
            createdBy: $userId,
            updatedBy: $userId,
            isDeleted: false,
            email: $dto->getEmail(),
            phone: $dto->getPhone(),
            company: $dto->getCompany(),
            city: $dto->getCity(),
            country: $dto->getCountry(),
            notes: $dto->getNotes(),
        );
    }
}
