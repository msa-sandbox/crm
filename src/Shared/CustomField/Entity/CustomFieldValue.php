<?php

declare(strict_types=1);

namespace App\Shared\CustomField\Entity;

use App\Shared\CustomField\Repository\CustomFieldValueRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomFieldValueRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'custom_field_values')]
#[ORM\Index(name: 'idx_cfv_entity', columns: ['entity_type', 'entity_id'])]
#[ORM\UniqueConstraint(
    name: 'uniq_field_entity_value',
    columns: ['custom_field_id', 'entity_type', 'entity_id']
)]
class CustomFieldValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: CustomField::class)]
    #[ORM\JoinColumn(name: 'custom_field_id', nullable: false, onDelete: 'CASCADE')]
    private CustomField $customField;

    // Entity type: 'lead', 'contact' ...
    #[ORM\Column(length: 50, nullable: false)]
    private string $entityType;

    // ID of the entity (Lead, Contact ...
    #[ORM\Column(type: 'integer', nullable: false)]
    private int $entityId;

    // Actual field value (stored as TEXT, can be JSON for complex types)
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $value;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        CustomField $customField,
        string $entityType,
        int $entityId,
        int $id = null,
        string $value = null,
    ) {
        $this->id = $id;
        $this->customField = $customField;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->value = $value;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
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

    public function getCustomField(): CustomField
    {
        return $this->customField;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getValueAsInt(): ?int
    {
        return null !== $this->value ? (int) $this->value : null;
    }

    public function getValueAsFloat(): ?float
    {
        return null !== $this->value ? (float) $this->value : null;
    }

    public function getValueAsBool(): ?bool
    {
        if (null === $this->value) {
            return null;
        }

        return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
    }
}
