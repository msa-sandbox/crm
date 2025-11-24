<?php

declare(strict_types=1);

namespace App\CRM\Lead\Repository;

use App\CRM\Lead\Entity\Lead;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LeadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lead::class);
    }

    public function save(Lead $contact, bool $flush = true): void
    {
        $this->getEntityManager()->persist($contact);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Lead $contact, bool $flush = true): void
    {
        $this->getEntityManager()->remove($contact);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find lead by ID, excluding deleted.
     */
    public function findActiveById(int $id): ?Lead
    {
        return $this->createQueryBuilder('l')
            ->where('l.id = :id')
            ->andWhere('l.is_deleted = :deleted')
            ->setParameter('id', $id)
            ->setParameter('deleted', false)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find leads by user ID.
     *
     * @return Lead[]
     */
    public function findByUserId(int $userId, bool $includeDeleted = false): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.user_id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('l.created_at', 'DESC');

        if (!$includeDeleted) {
            $qb->andWhere('l.is_deleted = :deleted')
                ->setParameter('deleted', false);
        }

        return $qb->getQuery()->getResult();
    }
}
