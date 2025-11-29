<?php

declare(strict_types=1);

namespace App\Api\V1\Dto\Request\Contact;

use App\CRM\Contact\Enum\IncludesEnum;
use Symfony\Component\Validator\Constraints as Assert;

readonly class GetContactQueryDto
{
    public function __construct(
        #[Assert\Type('numeric')]
        #[Assert\PositiveOrZero]
        private mixed $afterId = null,

        #[Assert\Type('numeric')]
        #[Assert\Positive]
        #[Assert\LessThanOrEqual(100)]
        private mixed $limit = 20,

        #[Assert\Type('bool')]
        private mixed $includeDeleted = false,

        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 150)]
        private mixed $search = null,

        #[Assert\Type('string')]
        #[Assert\Choice(callback: 'possibleIncludes')]
        private mixed $include = null,
    ) {
    }

    public function getAfterId(): ?int
    {
        return $this->afterId ? (int) $this->afterId : null;
    }

    public function getLimit(): int
    {
        return (int) $this->limit;
    }

    public function includeDeleted(): bool
    {
        return (bool) $this->includeDeleted;
    }

    public function getSearch(): ?string
    {
        return $this->search ? trim($this->search) : null;
    }

    public function getIncludes(): array
    {
        return $this->include
            ? array_filter(array_map('trim', explode(',', $this->include)))
            : [];
    }

    public static function possibleIncludes(): array
    {
        return array_map(fn (IncludesEnum $case) => $case->value, IncludesEnum::cases());
    }
}
