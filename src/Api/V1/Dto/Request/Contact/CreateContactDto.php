<?php

declare(strict_types=1);

namespace App\Api\V1\Dto\Request\Contact;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateContactDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 100)]
        private mixed $firstName = null,

        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 100)]
        private mixed $lastName = null,

        #[Assert\Type('string')]
        #[Assert\Length(min: 5, max: 150)]
        #[Assert\Email]
        private mixed $email = null,

        #[Assert\Type('string')]
        #[Assert\Length(min: 5, max: 20)]
        private mixed $phone = null,

        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 100)]
        private mixed $company = null,

        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 100)]
        private mixed $city = null,

        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 100)]
        private mixed $country = null,

        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 2000)]
        private mixed $notes = null,
    ) {
    }

    public function getFirstName(): string
    {
        return (string) $this->firstName;
    }

    public function getLastName(): string
    {
        return (string) $this->lastName;
    }

    public function getEmail(): ?string
    {
        return $this->email ? (string) $this->email : null;
    }

    public function getPhone(): ?string
    {
        return $this->phone ? (string) $this->phone : null;
    }

    public function getCompany(): ?string
    {
        return $this->company ? (string) $this->company : null;
    }

    public function getCity(): ?string
    {
        return $this->city ? (string) $this->city : null;
    }

    public function getCountry(): ?string
    {
        return $this->country ? (string) $this->country : null;
    }

    public function getNotes(): ?string
    {
        return $this->notes ? (string) $this->notes : null;
    }
}
