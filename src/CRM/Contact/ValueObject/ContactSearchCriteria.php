<?php

declare(strict_types=1);

namespace App\CRM\Contact\ValueObject;

readonly class ContactSearchCriteria
{
    public function __construct(
        private int $accountId,
        private int $limit,
        private ?int $afterId = null,
        private ?string $search = null,
        private bool $includeDeleted = false,
        private array $includes = [],
    ) {
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getAfterId(): ?int
    {
        return $this->afterId;
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function includeDeleted(): bool
    {
        return $this->includeDeleted;
    }

    public function getIncludes(): array
    {
        return $this->includes;
    }
}
