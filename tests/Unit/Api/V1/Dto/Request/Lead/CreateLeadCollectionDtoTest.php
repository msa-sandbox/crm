<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Dto\Request\Lead;

use App\Api\V1\Dto\Request\Lead\CreateLeadCollectionDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadDto;
use App\CRM\Lead\Enum\PipelineStageEnum;
use App\CRM\Lead\Enum\StatusEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateLeadCollectionDtoTest extends TestCase
{
    public function testCollectionValidation(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $collection = new CreateLeadCollectionDto([
            new CreateLeadDto('Lead 1', StatusEnum::ACTIVE->value, null, 100),
            new CreateLeadDto('Lead 2', StatusEnum::WON->value, PipelineStageEnum::CONTACTED->value, 200),
        ]);

        $violations = $validator->validate($collection);
        $this->assertCount(0, $violations);
        $this->assertCount(2, $collection->all());
    }

    public function testCollectionFailsOnInvalidItem(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $collection = new CreateLeadCollectionDto(['invalid']);
        $violations = $validator->validate($collection);
        $this->assertGreaterThan(0, $violations);
    }
}
