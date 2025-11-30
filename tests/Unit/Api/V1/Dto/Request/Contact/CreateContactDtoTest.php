<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Dto\Request\Contact;

use App\Api\V1\Dto\Request\Contact\CreateContactDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateContactDtoTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidDtoPassesValidation(): void
    {
        $dto = new CreateContactDto(
            'John', 'Doe', 'john@example.com',
            '+1234567890', 'Acme', 'New York', 'USA', 'Important client'
        );

        $violations = $this->validator->validate($dto);
        $this->assertCount(0, $violations);
    }

    public function testInvalidDtoFailsValidation(): void
    {
        $dto = new CreateContactDto(
            '', 'D', 'bademail', '123', '', '', '', ''
        );

        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, $violations);
    }
}
