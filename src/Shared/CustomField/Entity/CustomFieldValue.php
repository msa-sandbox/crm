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
    private int $id;

    #[ORM\ManyToOne(targetEntity: CustomField::class)]
    #[ORM\JoinColumn(name: 'custom_field_id', nullable: false, onDelete: 'CASCADE')]
    private CustomField $custom_field;

    // Entity type: 'lead', 'contact' ...
    #[ORM\Column(length: 50, nullable: false)]
    private string $entity_type;

    // ID of the entity (Lead, Contact ...
    #[ORM\Column(type: 'integer', nullable: false)]
    private int $entity_id;

    // Actual field value (stored as TEXT, can be JSON for complex types)
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $value = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $created_at;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updated_at;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new DateTimeImmutable();
        $this->created_at = $now;
        $this->updated_at = $now;
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

    public function getCustomField(): CustomField
    {
        return $this->custom_field;
    }

    public function getEntityType(): string
    {
        return $this->entity_type;
    }

    public function getEntityId(): int
    {
        return $this->entity_id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updated_at;
    }

    // Setters
    public function setCustomField(CustomField $custom_field): self
    {
        $this->custom_field = $custom_field;

        return $this;
    }

    public function setEntityType(string $entity_type): self
    {
        $this->entity_type = $entity_type;

        return $this;
    }

    public function setEntityId(int $entity_id): self
    {
        $this->entity_id = $entity_id;

        return $this;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    // Helper methods for typed values
    public function getValueAsArray(): ?array
    {
        if ($this->value === null) {
            return null;
        }

        $decoded = json_decode($this->value, true);
        return is_array($decoded) ? $decoded : null;
    }

    public function setValueFromArray(array $value): self
    {
        $this->value = json_encode($value);

        return $this;
    }

    public function getValueAsInt(): ?int
    {
        return $this->value !== null ? (int) $this->value : null;
    }

    public function getValueAsFloat(): ?float
    {
        return $this->value !== null ? (float) $this->value : null;
    }

    public function getValueAsBool(): ?bool
    {
        if ($this->value === null) {
            return null;
        }
        return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
    }
}
