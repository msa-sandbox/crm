<?php

declare(strict_types=1);

namespace App\CRM\Contact\Repository;

use App\CRM\Contact\Contract\ContactRepositoryInterface;
use App\CRM\Contact\Entity\Contact;
use App\CRM\Contact\Enum\RelationsEnum;
use App\CRM\Contact\ValueObject\ContactSearchCriteria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContactRepository extends ServiceEntityRepository implements ContactRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    public function add(Contact $lead): void
    {
        $this->getEntityManager()->persist($lead);
    }

    public function remove(Contact $lead): void
    {
        $this->getEntityManager()->remove($lead);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * Find contact by ID, excluding deleted.
     */
    public function findActiveById(int $id): ?Contact
    {
        return $this->createQueryBuilder('c')
            ->where('c.id = :id')
            ->andWhere('c.is_deleted = :deleted')
            ->setParameter('id', $id)
            ->setParameter('deleted', false)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findExistingByEmailsAndAccount(array $emails, int $accountId, bool $includeDeleted = false): array
    {
        if (!$emails) {
            return [];
        }

        $qb = $this->createQueryBuilder('c')
            ->where('c.accountId = :accountId')
            ->andWhere('c.email IN (:emails)')
            ->setParameter('accountId', $accountId)
            ->setParameter('emails', $emails);

        if (!$includeDeleted) {
            $qb->andWhere('c.isDeleted = false');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findByAccountId(ContactSearchCriteria $criteria): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.accountId = :accountId')
            ->setParameter('accountId', $criteria->getAccountId());

        if ($criteria->getAfterId()) {
            $qb->andWhere('c.id > :afterId')
                ->setParameter('afterId', $criteria->getAfterId());
        }

        if (!$criteria->includeDeleted()) {
            $qb->andWhere('c.isDeleted = false');
        }

        if ($criteria->getSearch()) {
            $qb->andWhere('
                LOWER(c.firstName) LIKE :search
                OR LOWER(c.lastName) LIKE :search
                OR LOWER(c.email) LIKE :search
                OR LOWER(c.company) LIKE :search
            ')
                ->setParameter('search', '%'.mb_strtolower($criteria->getSearch()).'%');
        }

        foreach ($criteria->getWith() as $with) {
            match ($with) {
                RelationsEnum::LEADS->value => $qb->leftJoin('c.leads', 'l')->addSelect('l'),
                default => null,
            };
        }

        return $qb
            ->orderBy('c.id', 'ASC')
            ->setMaxResults($criteria->getLimit())
            ->getQuery()
            ->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findOneById(int $id, int $accountId, bool $withLeads = false): ?Contact
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.id = :id')
            ->andWhere('c.accountId = :accountId')
            ->setParameter('id', $id)
            ->setParameter('accountId', $accountId);

        if ($withLeads) {
            $qb->leftJoin('c.leads', 'l')->addSelect('l');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
