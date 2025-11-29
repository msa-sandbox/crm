<?php

declare(strict_types=1);

namespace App\Api\V1\Dto\Request\Lead;

use App\CRM\Lead\Enum\RelationsEnum;
use Symfony\Component\Validator\Constraints as Assert;

readonly class GetLeadItemQueryDto
{
    public function __construct(
        #[Assert\Type('string')]
        #[Assert\Choice(callback: 'possibleWith')]
        private mixed $with = null,

        #[Assert\Type('bool')]
        private mixed $includeDeleted = false,
    ) {
    }

    public function getWith(): array
    {
        return $this->with
            ? array_filter(array_map('trim', explode(',', $this->with)))
            : [];
    }

    public function includeDeleted(): bool
    {
        return (bool) $this->includeDeleted;
    }

    public static function possibleWith(): array
    {
        return array_map(fn (RelationsEnum $case) => $case->value, RelationsEnum::cases());
    }
}
