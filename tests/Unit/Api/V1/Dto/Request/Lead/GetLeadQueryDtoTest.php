<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Dto\Request\Lead;

use App\Api\V1\Dto\Request\Lead\GetLeadQueryDto;
use App\CRM\Lead\Enum\RelationsEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class GetLeadQueryDtoTest extends TestCase
{
    public function testValidDtoPassesValidation(): void
    {
        $dto = new GetLeadQueryDto(
            afterId: 10,
            limit: 50,
            includeDeleted: true,
            search: 'Lead',
            with: RelationsEnum::CONTACTS->value
        );

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $this->assertCount(0, $validator->validate($dto));
        $this->assertSame(['contacts'], $dto->getWith());
    }

    public function testInvalidValuesFailValidation(): void
    {
        $dto = new GetLeadQueryDto(afterId: -1, limit: 200, includeDeleted: 'yes', search: 'A', with: 'invalid');
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $this->assertGreaterThan(0, $validator->validate($dto));
    }
}
