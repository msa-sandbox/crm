<?php

declare(strict_types=1);

namespace App\Api\V1\Dto\Request\Lead;

use App\Api\V1\Dto\DtoCollectionInterface;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateLeadCollectionDto implements DtoCollectionInterface
{
    /**
     * @param CreateLeadDto[] $leads
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Valid]
        #[Assert\All([
            new Assert\Type(type: CreateLeadDto::class),
        ])]
        private array $leads = [],
    ) {
    }

    public static function getItemClass(): string
    {
        return CreateLeadDto::class;
    }

    public static function getItemsProperty(): string
    {
        return 'leads';
    }

    /**
     * @return CreateLeadDto[]
     */
    public function all(): array
    {
        return $this->leads;
    }
}
