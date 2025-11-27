<?php

declare(strict_types=1);

namespace App\CRM\Lead\Repository;

use App\CRM\Lead\Contract\LeadRepositoryInterface;
use App\CRM\Lead\Entity\Lead;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LeadRepository extends ServiceEntityRepository implements LeadRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lead::class);
    }

    public function add(Lead $lead): void
    {
        $this->getEntityManager()->persist($lead);
    }

    public function remove(Lead $lead): void
    {
        $this->getEntityManager()->remove($lead);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
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
    public function findByAccountId(int $accountId, bool $includeDeleted = false): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.account_id = :accountId')
            ->setParameter('accountId', $accountId)
            ->orderBy('l.created_at', 'DESC');

        if (!$includeDeleted) {
            $qb->andWhere('l.is_deleted = :deleted')
                ->setParameter('deleted', false);
        }

        return $qb->getQuery()->getResult();
    }
}
