<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Factory;

use App\Api\V1\Dto\Request\Contact\CreateContactCollection;
use App\Api\V1\Dto\Request\Contact\CreateContactDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadWithContactDto;
use App\Api\V1\Factory\ContactFactory;
use App\Api\V1\Factory\LeadFactory;
use App\CRM\Contact\Entity\Contact;
use App\CRM\Lead\Entity\Lead;
use App\CRM\Lead\Enum\StatusEnum;
use PHPUnit\Framework\TestCase;

final class LeadFactoryTest extends TestCase
{
    public function testFromDtoCreatesLead(): void
    {
        $dto = new CreateLeadDto(
            title: 'Lead A',
            status: StatusEnum::ACTIVE->value,
            pipelineStage: null,
            budget: 500,
            description: 'Test',
            notes: 'Note'
        );

        $factory = new LeadFactory(new ContactFactory());
        $lead = $factory->fromDto($dto, accountId: 5, userId: 99);

        $this->assertInstanceOf(Lead::class, $lead);
        $this->assertSame('Lead A', $lead->getTitle());
        $this->assertSame(StatusEnum::ACTIVE->value, $lead->getStatus());
        $this->assertSame('500.00', $lead->getBudget());
        $this->assertSame(5, $lead->getAccountId());
        $this->assertSame(99, $lead->getUserId());
    }

    public function testFromDtoWithContactsCreatesLeadAndAddsContacts(): void
    {
        $contactDto = new CreateContactDto('John', 'Doe', 'john@example.com');
        $contacts = new CreateContactCollection([$contactDto]);

        $dto = new CreateLeadWithContactDto(
            title: 'Complex Lead',
            status: StatusEnum::ACTIVE->value,
            pipelineStage: null,
            budget: 1000,
            description: 'desc',
            notes: 'note',
            _embedded: $contacts
        );

        $contactFactory = $this->createMock(ContactFactory::class);
        $contactFactory->expects($this->once())
            ->method('fromDto')
            ->with($contactDto, 1, 77)
            ->willReturn($this->createMock(Contact::class));

        $factory = new LeadFactory($contactFactory);
        $lead = $factory->fromDtoWithContacts($dto, 1, 77);

        $this->assertInstanceOf(Lead::class, $lead);
        $this->assertSame('Complex Lead', $lead->getTitle());
    }
}
