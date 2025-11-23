<?php

declare(strict_types=1);

namespace App\Shared\CustomField\Entity;

use App\Shared\CustomField\Repository\CustomFieldRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomFieldRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'custom_fields')]
#[ORM\Index(name: 'idx_cf_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_cf_entity_type', columns: ['entity_type'])]
#[ORM\Index(name: 'idx_cf_user_entity', columns: ['user_id', 'entity_type'])]
#[ORM\UniqueConstraint(name: 'uniq_user_entity_field', columns: ['user_id', 'entity_type', 'field_key'])]
class CustomField
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    // User from another microservice (only ID)
    #[ORM\Column(type: 'integer', nullable: false)]
    private int $user_id;

    // Entity type: 'lead', 'contact', 'deal', 'company'
    #[ORM\Column(length: 50, nullable: false)]
    private string $entity_type;

    // Unique field key within entity_type
    #[ORM\Column(length: 100, nullable: false)]
    private string $field_key;

    // Human-readable field label
    #[ORM\Column(length: 150, nullable: false)]
    private string $label;

    // Field data type: 'string', 'number', 'boolean', 'date', 'select', 'multiselect'
    #[ORM\Column(length: 50, nullable: false)]
    private string $field_type;

    // Additional options stored as JSON (e.g., select options, validation rules)
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $options = null;

    // Is this field required?
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $is_required = false;

    // Can this field be edited/deleted?
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $is_editable = true;

    // Is this a system field? (e.g., email, phone)
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $is_system = false;

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

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getEntityType(): string
    {
        return $this->entity_type;
    }

    public function getFieldKey(): string
    {
        return $this->field_key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getFieldType(): string
    {
        return $this->field_type;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function isRequired(): bool
    {
        return $this->is_required;
    }

    public function isEditable(): bool
    {
        return $this->is_editable;
    }

    public function isSystem(): bool
    {
        return $this->is_system;
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
    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function setEntityType(string $entity_type): self
    {
        $this->entity_type = $entity_type;

        return $this;
    }

    public function setFieldKey(string $field_key): self
    {
        $this->field_key = $field_key;

        return $this;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function setFieldType(string $field_type): self
    {
        $this->field_type = $field_type;

        return $this;
    }

    public function setOptions(?array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function setIsRequired(bool $is_required): self
    {
        $this->is_required = $is_required;

        return $this;
    }

    public function setIsEditable(bool $is_editable): self
    {
        $this->is_editable = $is_editable;

        return $this;
    }

    public function setIsSystem(bool $is_system): self
    {
        $this->is_system = $is_system;

        return $this;
    }
}
