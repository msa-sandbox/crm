<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Dto\Request\Contact;

use App\Api\V1\Dto\Request\Contact\GetContactQueryDto;
use App\CRM\Contact\Enum\RelationsEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class GetContactQueryDtoTest extends TestCase
{
    public function testValidDtoPassesValidation(): void
    {
        $dto = new GetContactQueryDto(
            afterId: 10,
            limit: 25,
            includeDeleted: true,
            search: 'John',
            with: RelationsEnum::LEADS->value
        );

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($dto);
        $this->assertCount(0, $violations);
    }

    public function testInvalidDtoFailsValidation(): void
    {
        $dto = new GetContactQueryDto(
            afterId: -1,
            limit: 999,
            includeDeleted: 'not_bool',
            search: 'A',
            with: 'invalid'
        );

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($dto);
        $this->assertGreaterThan(0, $violations);
    }

    public function testGettersReturnCorrectValues(): void
    {
        $dto = new GetContactQueryDto(
            afterId: 5,
            limit: 10,
            includeDeleted: true,
            search: '  John ',
            with: 'contacts,leads'
        );

        $this->assertSame(5, $dto->getAfterId());
        $this->assertSame(10, $dto->getLimit());
        $this->assertTrue($dto->includeDeleted());
        $this->assertSame('John', $dto->getSearch());
        $this->assertSame(['contacts', 'leads'], $dto->getWith());
    }
}
