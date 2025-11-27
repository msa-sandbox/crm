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
    private ?int $id;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $accountId;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $userId;

    // Entity type: 'lead', 'contact', 'deal', 'company'
    #[ORM\Column(length: 50, nullable: false)]
    private string $entityType;

    // Unique field key within entity_type
    #[ORM\Column(length: 100, nullable: false)]
    private string $fieldKey;

    // Human-readable field label
    #[ORM\Column(length: 150, nullable: false)]
    private string $label;

    // Field data type: 'string', 'number', 'boolean', 'date', 'select', 'multiselect'
    #[ORM\Column(length: 50, nullable: false)]
    private string $fieldType;

    // Additional options stored as JSON (e.g., select options, validation rules)
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $options;

    // Is this field required?
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isRequired;

    // Can this field be edited/deleted?
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isEditable;

    // Is this a system field? (e.g., email, phone)
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isSystem;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $created_at;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updated_at;

    public function __construct(
        int $accountId,
        int $userId,
        string $entityType,
        string $fieldKey,
        string $label,
        string $fieldType,
        ?int $id = null,
        ?array $options = null,
        bool $isRequired = false,
        bool $isEditable = true,
        bool $isSystem = false,
    ) {
        $this->accountId = $accountId;
        $this->userId = $userId;
        $this->entityType = $entityType;
        $this->fieldKey = $fieldKey;
        $this->label = $label;
        $this->fieldType = $fieldType;
        $this->id = $id;
        $this->options = $options;
        $this->isRequired = $isRequired;
        $this->isEditable = $isEditable;
        $this->isSystem = $isSystem;
    }

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

    public function getAccountId(): int
    {
        return $this->accountId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getFieldKey(): string
    {
        return $this->fieldKey;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getFieldType(): string
    {
        return $this->fieldType;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function isEditable(): bool
    {
        return $this->isEditable;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updated_at;
    }
}
