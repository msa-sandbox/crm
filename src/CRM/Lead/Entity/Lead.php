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
    private int $id;

    #[ORM\Column(length: 100, nullable: false)]
    private string $title;

    #[ORM\Column(length: 50, nullable: false)]
    private string $status;

    #[ORM\Column(length: 50, nullable: false)]
    private string $pipeline_stage;

    #[ORM\Column(type: 'decimal', nullable: false)]
    private string $budget;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $description = null;

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
     * @var Collection<int, Contact>
     */
    #[ORM\ManyToMany(targetEntity: Contact::class, inversedBy: 'leads')]
    #[ORM\JoinTable(
        name: 'lead_contacts',
        joinColumns: [new ORM\JoinColumn(name: 'lead_id', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'contact_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    )]
    private Collection $contacts;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
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
        return $this->pipeline_stage;
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
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    // Setters
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function setPipelineStage(string $pipeline_stage): self
    {
        $this->pipeline_stage = $pipeline_stage;

        return $this;
    }

    public function setBudget(string $budget): self
    {
        $this->budget = $budget;

        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

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
     * Get array of contact IDs
     *
     * @return int[]
     */
    public function getContactIds(): array
    {
        return $this->contacts->map(fn(Contact $contact) => $contact->getId())->toArray();
    }
}
