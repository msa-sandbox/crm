<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Dto\Request\Contact;

use App\Api\V1\Dto\Request\Contact\GetContactItemQueryDto;
use App\CRM\Contact\Enum\RelationsEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class GetContactItemQueryDtoTest extends TestCase
{
    public function testValidDtoPassesValidation(): void
    {
        $dto = new GetContactItemQueryDto(
            with: RelationsEnum::LEADS->value,
            includeDeleted: false
        );

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($dto);
        $this->assertCount(0, $violations);
    }

    public function testInvalidWithFailsValidation(): void
    {
        $dto = new GetContactItemQueryDto(with: 'unknown,wrong');

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($dto);
        $this->assertGreaterThan(0, $violations);
    }

    public function testGettersReturnCorrectValues(): void
    {
        $dto = new GetContactItemQueryDto(with: 'contacts, leads', includeDeleted: true);

        $this->assertTrue($dto->includeDeleted());
        $this->assertSame(['contacts', 'leads'], $dto->getWith());
    }
}
