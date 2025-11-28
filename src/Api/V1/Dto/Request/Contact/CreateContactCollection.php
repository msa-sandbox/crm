<?php

declare(strict_types=1);

namespace App\Api\V1\Dto\Request\Contact;

use App\Api\V1\Dto\DtoCollectionInterface;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateContactCollection implements DtoCollectionInterface
{
    /**
     * @param CreateContactDto[] $contacts
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Valid]
        #[Assert\All([
            new Assert\Type(type: CreateContactDto::class),
        ])]
        private array $contacts = [],
    ) {
    }

    public static function getItemClass(): string
    {
        return CreateContactDto::class;
    }

    public static function getItemsProperty(): string
    {
        return 'contacts';
    }

    /**
     * @return CreateContactDto[]
     */
    public function all(): array
    {
        return $this->contacts;
    }
}
