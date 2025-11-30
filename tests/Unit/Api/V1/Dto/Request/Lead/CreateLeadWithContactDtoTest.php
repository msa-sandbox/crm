<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Dto\Request\Lead;

use App\Api\V1\Dto\Request\Contact\CreateContactCollection;
use App\Api\V1\Dto\Request\Contact\CreateContactDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadWithContactDto;
use App\CRM\Lead\Enum\StatusEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateLeadWithContactDtoTest extends TestCase
{
    public function testValidDtoWithContactsPassesValidation(): void
    {
        $contacts = new CreateContactCollection([
            new CreateContactDto('John', 'Doe', 'john@example.com'),
        ]);

        $dto = new CreateLeadWithContactDto(
            title: 'Lead with contact',
            status: StatusEnum::ACTIVE->value,
            pipelineStage: null,
            budget: 2000,
            description: 'desc',
            notes: 'note',
            _embedded: $contacts
        );

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($dto);
        $this->assertCount(0, $violations);
        $this->assertSame($contacts, $dto->getEmbedded());
    }

    public function testMissingContactsFailsValidation(): void
    {
        $dto = new CreateLeadWithContactDto(
            'Lead', StatusEnum::ACTIVE->value, null, 200, null, null, null
        );

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($dto);
        $this->assertGreaterThan(0, $violations);
    }
}
