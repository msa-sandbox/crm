<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Dto\Request\Lead;

use App\Api\V1\Dto\Request\Lead\GetLeadItemQueryDto;
use App\CRM\Lead\Enum\RelationsEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class GetLeadItemQueryDtoTest extends TestCase
{
    public function testValidDtoPassesValidation(): void
    {
        $dto = new GetLeadItemQueryDto(RelationsEnum::CONTACTS->value, false);
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $this->assertCount(0, $validator->validate($dto));
        $this->assertSame([$dto->getWith()[0]], [RelationsEnum::CONTACTS->value]);
    }

    public function testInvalidChoiceFailsValidation(): void
    {
        $dto = new GetLeadItemQueryDto('wrong');
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($dto);
        $this->assertGreaterThan(0, $violations);
    }
}
