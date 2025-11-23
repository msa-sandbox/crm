<?php

declare(strict_types=1);

namespace App\CRM\Contact\Entity;

use App\CRM\Contact\Repository\ContactRepository;
use App\CRM\Lead\Entity\Lead;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'contacts')]
#[ORM\Index(name: 'idx_contacts_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_contacts_user_active', columns: ['user_id', 'is_deleted'])]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 100, nullable: false)]
    private string $first_name;

    #[ORM\Column(length: 100, nullable: false)]
    private string $last_name;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $is_deleted = false;

    // User from another microservice (only ID)
    #[ORM\Column(type: 'integer', nullable: false)]
    private int $user_id;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $created_by;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $updated_by;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private DateTimeImmutable $created_at;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private DateTimeImmutable $updated_at;

    /**
     * @var Collection<int, Lead>
     */
    #[ORM\ManyToMany(targetEntity: Lead::class, mappedBy: 'contacts')]
    private Collection $leads;

    public function __construct()
    {
        $this->leads = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updated_at = new DateTimeImmutable();
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function getLastName(): string
    {
        return $this->last_name;
    }

    public function getFullName(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->last_name,
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
        return $this->is_deleted;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getCreatedBy(): int
    {
        return $this->created_by;
    }

    public function getUpdatedBy(): int
    {
        return $this->updated_by;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updated_at;
    }

    /**
     * @return Collection<int, Lead>
     */
    public function getLeads(): Collection
    {
        return $this->leads;
    }

    /**
     * Get array of lead IDs
     *
     * @return int[]
     */
    public function getLeadIds(): array
    {
        return $this->leads->map(fn(Lead $lead) => $lead->getId())->toArray();
    }

    // Setters
    public function setFirstName(string $first_name): self
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function setLastName(string $last_name): self
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function setIsDeleted(bool $is_deleted): self
    {
        $this->is_deleted = $is_deleted;

        return $this;
    }

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function setCreatedBy(int $created_by): self
    {
        $this->created_by = $created_by;

        return $this;
    }

    public function setUpdatedBy(int $updated_by): self
    {
        $this->updated_by = $updated_by;

        return $this;
    }
}
