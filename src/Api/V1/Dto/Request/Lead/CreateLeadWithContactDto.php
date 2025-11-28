<?php

declare(strict_types=1);

namespace App\Api\V1\Dto\Request\Lead;

use App\Api\V1\Dto\Request\Contact\CreateContactCollection;
use App\CRM\Lead\Enum\PipelineStageEnum;
use App\CRM\Lead\Enum\StatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateLeadWithContactDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 100)]
        private mixed $title = null,

        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 50)]
        #[Assert\Choice(callback: 'possibleStatuses')]
        private mixed $status = null,

        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 50)]
        #[Assert\Choice(callback: 'possiblePipelineStages')]
        private mixed $pipelineStage = null,

        #[Assert\NotBlank]
        #[Assert\Type('numeric')]
        #[Assert\PositiveOrZero]
        private mixed $budget = null,

        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 200)]
        private mixed $description = null,

        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 1000)]
        private mixed $notes = null,

        #[Assert\Valid]
        private ?CreateContactCollection $_embedded = null,
    ) {
    }

    public function getTitle(): string
    {
        return (string) $this->title;
    }

    public function getStatus(): string
    {
        return (string) $this->status;
    }

    public function getPipelineStage(): ?string
    {
        return $this->pipelineStage ? (string) $this->pipelineStage : null;
    }

    public function getBudget(): string
    {
        return number_format((float) $this->budget, 2, '.', '');
    }

    public function getDescription(): ?string
    {
        return $this->description ? (string) $this->description : null;
    }

    public function getNotes(): ?string
    {
        return $this->notes ? (string) $this->notes : null;
    }

    public static function possibleStatuses(): array
    {
        return array_map(fn (StatusEnum $case) => $case->value, StatusEnum::cases());
    }

    public static function possiblePipelineStages(): array
    {
        return array_map(fn (PipelineStageEnum $case) => $case->value, PipelineStageEnum::cases());
    }

    public function getEmbedded(): ?CreateContactCollection
    {
        return $this->_embedded;
    }
}
