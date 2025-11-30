<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Dto\Request\Lead;

use App\Api\V1\Dto\Request\Contact\CreateContactCollection;
use App\Api\V1\Dto\Request\Contact\CreateContactDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadWithContactCollectionDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadWithContactDto;
use App\CRM\Lead\Enum\StatusEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateLeadWithContactCollectionDtoTest extends TestCase
{
    public function testValidCollection(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $contacts = new CreateContactCollection([
            new CreateContactDto('John', 'Doe', 'john@example.com'),
        ]);

        $collection = new CreateLeadWithContactCollectionDto([
            new CreateLeadWithContactDto('Lead', StatusEnum::ACTIVE->value, null, 200, null, null, $contacts),
        ]);

        $violations = $validator->validate($collection);
        $this->assertCount(0, $violations);
    }

    public function testInvalidCollectionFails(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $collection = new CreateLeadWithContactCollectionDto(['invalid']);
        $violations = $validator->validate($collection);
        $this->assertGreaterThan(0, $violations);
    }
}
