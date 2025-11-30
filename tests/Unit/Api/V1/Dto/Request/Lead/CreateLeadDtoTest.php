<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Dto\Request\Lead;

use App\Api\V1\Dto\Request\Lead\CreateLeadDto;
use App\CRM\Lead\Enum\PipelineStageEnum;
use App\CRM\Lead\Enum\StatusEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateLeadDtoTest extends TestCase
{
    public function testValidDtoPassesValidation(): void
    {
        $dto = new CreateLeadDto(
            title: 'Project X',
            status: StatusEnum::ACTIVE->value,
            pipelineStage: PipelineStageEnum::NEGOTIATION->value,
            budget: 5000,
            description: 'Some description',
            notes: 'Internal note'
        );

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($dto);
        $this->assertCount(0, $violations);

        $this->assertSame('5000.00', $dto->getBudget());
    }

    public function testInvalidDtoFailsValidation(): void
    {
        $dto = new CreateLeadDto('', 'invalid', 'wrong', -10, '', '');
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($dto);
        $this->assertGreaterThan(0, $violations);
    }
}
