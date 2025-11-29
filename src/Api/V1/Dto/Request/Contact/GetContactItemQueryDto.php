<?php

declare(strict_types=1);

namespace App\Api\V1\Dto\Request\Contact;

use App\CRM\Contact\Enum\IncludesEnum;
use Symfony\Component\Validator\Constraints as Assert;

readonly class GetContactItemQueryDto
{
    public function __construct(
        #[Assert\Type('string')]
        #[Assert\Choice(callback: 'possibleIncludes')]
        private mixed $include = null,

        #[Assert\Type('bool')]
        private mixed $includeDeleted = false,
    ) {
    }

    public function getIncludes(): array
    {
        return $this->include
            ? array_filter(array_map('trim', explode(',', $this->include)))
            : [];
    }

    public function includeDeleted(): bool
    {
        return (bool) $this->includeDeleted;
    }

    public static function possibleIncludes(): array
    {
        return array_map(fn (IncludesEnum $case) => $case->value, IncludesEnum::cases());
    }
}
