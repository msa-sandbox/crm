<?php

declare(strict_types=1);

namespace App\CRM\Lead\Entity;

use App\CRM\Contact\Entity\Contact;
use App\CRM\Lead\Repository\LeadRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LeadRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'leads')]
#[ORM\Index(name: 'idx_leads_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_leads_user_active', columns: ['user_id', 'is_deleted'])]
#[ORM\Index(name: 'idx_leads_user_stage_active', columns: ['user_id', 'pipeline_stage', 'is_deleted'])]
class Lead
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column]
    private ?int $id;

    #[ORM\Column(length: 100, nullable: false)]
    private string $title;

    #[ORM\Column(length: 50, nullable: false)]
    private string $status;

    #[ORM\Column(length: 50, nullable: false)]
    private string $pipelineStage;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: false)]
    private string $budget;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isDeleted = false;

    // User from another microservice (only ID)
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
     * @var Collection<int, Contact>
     */
    #[ORM\ManyToMany(targetEntity: Contact::class, inversedBy: 'leads')]
    #[ORM\JoinTable(
        name: 'lead_contacts',
        joinColumns: [new ORM\JoinColumn(name: 'lead_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'contact_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    )]
    private Collection $contacts;

    public function __construct(
        string $title,
        string $status,
        string $pipelineStage,
        string $budget,
        int $userId,
        int $createdBy,
        int $updatedBy,
        ?int $id = null,
        ?string $description = null,
        ?string $notes = null,
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->status = $status;
        $this->pipelineStage = $pipelineStage;
        $this->budget = $budget;
        $this->description = $description;
        $this->notes = $notes;
        $this->userId = $userId;
        $this->createdBy = $createdBy;
        $this->updatedBy = $updatedBy;
        $this->contacts = new ArrayCollection();
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPipelineStage(): string
    {
        return $this->pipelineStage;
    }

    public function getBudget(): string
    {
        return $this->budget;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
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
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    // Contact management methods
    public function addContact(Contact $contact): self
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
        }

        return $this;
    }

    public function removeContact(Contact $contact): self
    {
        $this->contacts->removeElement($contact);

        return $this;
    }

    public function hasContact(Contact $contact): bool
    {
        return $this->contacts->contains($contact);
    }

    public function clearContacts(): self
    {
        $this->contacts->clear();

        return $this;
    }

    /**
     * Get array of contact IDs.
     *
     * @return int[]
     */
    public function getContactIds(): array
    {
        return $this->contacts->map(fn (Contact $contact) => $contact->getId())->toArray();
    }
}
