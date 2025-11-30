<?php

declare(strict_types=1);

namespace App\CRM\Contact\Entity;

use App\CRM\Contact\Repository\ContactRepository;
use App\CRM\Lead\Entity\Lead;
use App\Exception\DomainException;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'contacts')]
#[ORM\Index(name: 'idx_contacts_account_id', columns: ['account_id'])]
#[ORM\Index(name: 'idx_contacts_account_active', columns: ['account_id', 'is_deleted'])]
#[ORM\Index(name: 'idx_contacts_account_email_active', columns: ['account_id', 'email', 'is_deleted'])] // soft unique index
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column]
    private ?int $id;

    #[ORM\Column(length: 100, nullable: false)]
    private string $firstName;

    #[ORM\Column(length: 100, nullable: false)]
    private string $lastName;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $email;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $company;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isDeleted;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $accountId;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $userId;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $createdBy;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $updatedBy;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, Lead>
     */
    #[ORM\ManyToMany(targetEntity: Lead::class, mappedBy: 'contacts')]
    private Collection $leads;

    public function __construct(
        string $firstName,
        string $lastName,
        int $accountId,
        int $userId,
        int $createdBy,
        int $updatedBy,
        ?int $id = null,
        bool $isDeleted = false,
        ?string $email = null,
        ?string $phone = null,
        ?string $company = null,
        ?string $city = null,
        ?string $country = null,
        ?string $notes = null,
    ) {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->accountId = $accountId;
        $this->userId = $userId;
        $this->createdBy = $createdBy;
        $this->updatedBy = $updatedBy;
        $this->isDeleted = $isDeleted;
        $this->email = $email;
        $this->phone = $phone;
        $this->company = $company;
        $this->city = $city;
        $this->country = $country;
        $this->notes = $notes;

        $this->leads = new ArrayCollection();
    }

    /**
     * If there was a request to create a new contact, but we have already found it in the database,
     * we need to update it with new data from the source.
     *
     * @param Contact $source
     *
     * @return void
     *
     * @throws DomainException
     */
    public function updateFrom(Contact $source): void
    {
        // Check: forbidden to update contact from another account
        if ($this->accountId !== $source->getAccountId()) {
            throw new DomainException('Cannot update contact from different account');
        }

        $this->firstName = $source->getFirstName();
        $this->lastName = $source->getLastName();
        $this->phone = $source->getPhone();
        $this->company = $source->getCompany();
        $this->city = $source->getCity();
        $this->country = $source->getCountry();
        $this->notes = $source->getNotes();
        $this->updatedBy = $source->getUpdatedBy();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        $parts = array_filter([
            $this->firstName,
            $this->lastName,
        ]);

        return implode(' ', $parts);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function getUpdatedBy(): int
    {
        return $this->updatedBy;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, Lead>
     */
    public function getLeads(): Collection
    {
        return $this->leads;
    }

    /**
     * Get array of lead IDs.
     *
     * @return int[]
     */
    public function getLeadIds(): array
    {
        return $this->leads->map(fn (Lead $lead) => $lead->getId())->toArray();
    }

    // Lead management methods
    public function addLead(Lead $lead): self
    {
        if (!$this->leads->contains($lead)) {
            $this->leads->add($lead);
        }

        return $this;
    }

    public function removeLead(Lead $lead): self
    {
        $this->leads->removeElement($lead);

        return $this;
    }

    public function hasLead(Lead $lead): bool
    {
        return $this->leads->contains($lead);
    }

    public function clearLeads(): self
    {
        $this->leads->clear();

        return $this;
    }
}
