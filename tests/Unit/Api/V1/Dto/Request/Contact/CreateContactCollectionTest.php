<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Dto\Request\Contact;

use App\Api\V1\Dto\Request\Contact\CreateContactCollection;
use App\Api\V1\Dto\Request\Contact\CreateContactDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateContactCollectionTest extends TestCase
{
    public function testValidCollectionPassesValidation(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $collection = new CreateContactCollection([
            new CreateContactDto('John', 'Doe', 'john@example.com'),
            new CreateContactDto('Jane', 'Smith', 'jane@example.com'),
        ]);

        $violations = $validator->validate($collection);
        $this->assertCount(0, $violations);
        $this->assertCount(2, $collection->all());
    }

    public function testInvalidCollectionFailsValidation(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        // invalid dto
        $collection = new CreateContactCollection(['not_a_dto']);

        $violations = $validator->validate($collection);
        $this->assertGreaterThan(0, $violations);
    }
}
