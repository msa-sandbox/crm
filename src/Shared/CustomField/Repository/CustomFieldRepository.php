<?php

declare(strict_types=1);

namespace App\Shared\CustomField\Repository;

use App\Shared\CustomField\Entity\CustomField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomField>
 */
class CustomFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomField::class);
    }

    public function save(CustomField $field, bool $flush = true): void
    {
        $this->getEntityManager()->persist($field);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CustomField $field, bool $flush = true): void
    {
        $this->getEntityManager()->remove($field);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all fields for a specific user and entity type.
     *
     * @return CustomField[]
     */
    public function findByUserAndEntityType(int $userId, string $entityType): array
    {
        return $this->createQueryBuilder('cf')
            ->where('cf.user_id = :userId')
            ->andWhere('cf.entity_type = :entityType')
            ->setParameter('userId', $userId)
            ->setParameter('entityType', $entityType)
            ->orderBy('cf.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find fields by user, entity type and field key.
     */
    public function findByUserEntityTypeAndKey(int $userId, string $entityType, string $fieldKey): ?CustomField
    {
        return $this->createQueryBuilder('cf')
            ->where('cf.user_id = :userId')
            ->andWhere('cf.entity_type = :entityType')
            ->andWhere('cf.field_key = :fieldKey')
            ->setParameter('userId', $userId)
            ->setParameter('entityType', $entityType)
            ->setParameter('fieldKey', $fieldKey)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all required fields for a user and entity type.
     *
     * @return CustomField[]
     */
    public function findRequiredByUserAndEntityType(int $userId, string $entityType): array
    {
        return $this->createQueryBuilder('cf')
            ->where('cf.user_id = :userId')
            ->andWhere('cf.entity_type = :entityType')
            ->andWhere('cf.is_required = :required')
            ->setParameter('userId', $userId)
            ->setParameter('entityType', $entityType)
            ->setParameter('required', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all editable fields for a user and entity type.
     *
     * @return CustomField[]
     */
    public function findEditableByUserAndEntityType(int $userId, string $entityType): array
    {
        return $this->createQueryBuilder('cf')
            ->where('cf.user_id = :userId')
            ->andWhere('cf.entity_type = :entityType')
            ->andWhere('cf.is_editable = :editable')
            ->setParameter('userId', $userId)
            ->setParameter('entityType', $entityType)
            ->setParameter('editable', true)
            ->getQuery()
            ->getResult();
    }
}
