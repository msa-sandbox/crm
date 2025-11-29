<?php

declare(strict_types=1);

namespace App\CRM\Lead\Repository;

use App\CRM\Lead\Contract\LeadRepositoryInterface;
use App\CRM\Lead\Entity\Lead;
use App\CRM\Lead\Enum\RelationsEnum;
use App\CRM\Lead\ValueObject\LeadSearchCriteria;
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
    public function findByAccountId(LeadSearchCriteria $criteria): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.accountId = :accountId')
            ->setParameter('accountId', $criteria->getAccountId());

        if ($criteria->getAfterId()) {
            $qb->andWhere('l.id > :afterId')
                ->setParameter('afterId', $criteria->getAfterId());
        }

        if (!$criteria->includeDeleted()) {
            $qb->andWhere('l.isDeleted = false');
        }

        if ($criteria->getSearch()) {
            $qb->andWhere('
                LOWER(l.firstName) LIKE :search
                OR LOWER(l.lastName) LIKE :search
                OR LOWER(l.email) LIKE :search
                OR LOWER(l.company) LIKE :search
            ')
                ->setParameter('search', '%'.mb_strtolower($criteria->getSearch()).'%');
        }

        foreach ($criteria->getWith() as $with) {
            match ($with) {
                RelationsEnum::CONTACTS->value => $qb->leftJoin('l.contacts', 'c')->addSelect('c'),
                default => null,
            };
        }

        return $qb
            ->orderBy('l.id', 'ASC')
            ->setMaxResults($criteria->getLimit())
            ->getQuery()
            ->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findOneById(int $id, int $accountId, bool $withContacts = false): ?Lead
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.id = :id')
            ->andWhere('l.accountId = :accountId')
            ->setParameter('id', $id)
            ->setParameter('accountId', $accountId);

        if ($withContacts) {
            $qb->leftJoin('l.contacts', 'c')->addSelect('c');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
