<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Factory;

use App\Api\V1\Dto\Request\Contact\CreateContactDto;
use App\Api\V1\Factory\ContactFactory;
use App\CRM\Contact\Entity\Contact;
use PHPUnit\Framework\TestCase;

final class ContactFactoryTest extends TestCase
{
    public function testFromDtoCreatesValidContactEntity(): void
    {
        $dto = new CreateContactDto(
            'John', 'Doe', 'john@example.com',
            '+123456789', 'Acme', 'New York', 'USA', 'Important client'
        );

        $factory = new ContactFactory();
        $contact = $factory->fromDto($dto, accountId: 1, userId: 42);

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertSame('John', $contact->getFirstName());
        $this->assertSame('Doe', $contact->getLastName());
        $this->assertSame('john@example.com', $contact->getEmail());
        $this->assertSame('Acme', $contact->getCompany());
        $this->assertSame(1, $contact->getAccountId());
        $this->assertSame(42, $contact->getUserId());
    }
}
