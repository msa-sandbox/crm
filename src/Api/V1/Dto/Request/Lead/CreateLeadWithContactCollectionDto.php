<?php

declare(strict_types=1);

namespace App\Api\V1\Dto\Request\Lead;

use App\Api\V1\Dto\DtoCollectionInterface;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateLeadWithContactCollectionDto implements DtoCollectionInterface
{
    /**
     * @param CreateLeadWithContactDto[] $leads
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Valid]
        #[Assert\All([
            new Assert\Type(type: CreateLeadWithContactDto::class),
        ])]
        public array $leads = []
    ) {}

    public static function getItemClass(): string
    {
        return CreateLeadWithContactDto::class;
    }

    public static function getItemsProperty(): string
    {
        return 'leads';
    }

    /**
     * @return CreateLeadWithContactDto[]
     */
    public function all(): array
    {
        return $this->leads;
    }
}
