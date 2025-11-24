<?php

declare(strict_types=1);

namespace App\Shared\CustomField\Repository;

use App\Shared\CustomField\Entity\CustomFieldValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomFieldValue>
 */
class CustomFieldValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomFieldValue::class);
    }

    public function save(CustomFieldValue $value, bool $flush = true): void
    {
        $this->getEntityManager()->persist($value);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CustomFieldValue $value, bool $flush = true): void
    {
        $this->getEntityManager()->remove($value);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all values for a specific entity.
     *
     * @return CustomFieldValue[]
     */
    public function findByEntity(string $entityType, int $entityId): array
    {
        return $this->createQueryBuilder('cfv')
            ->where('cfv.entity_type = :entityType')
            ->andWhere('cfv.entity_id = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all values for multiple entities (batch loading).
     *
     * @param int[] $entityIds
     *
     * @return CustomFieldValue[]
     */
    public function findByEntities(string $entityType, array $entityIds): array
    {
        if (empty($entityIds)) {
            return [];
        }

        return $this->createQueryBuilder('cfv')
            ->where('cfv.entity_type = :entityType')
            ->andWhere('cfv.entity_id IN (:entityIds)')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityIds', $entityIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find value by field definition and entity.
     */
    public function findByFieldAndEntity(
        int $fieldDefinitionId,
        string $entityType,
        int $entityId,
    ): ?CustomFieldValue {
        return $this->createQueryBuilder('cfv')
            ->where('cfv.field_definition = :fieldDefinitionId')
            ->andWhere('cfv.entity_type = :entityType')
            ->andWhere('cfv.entity_id = :entityId')
            ->setParameter('fieldDefinitionId', $fieldDefinitionId)
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Delete all values for a specific entity.
     */
    public function deleteByEntity(string $entityType, int $entityId): void
    {
        $this->createQueryBuilder('cfv')
            ->delete()
            ->where('cfv.entity_type = :entityType')
            ->andWhere('cfv.entity_id = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->getQuery()
            ->execute();
    }
}
