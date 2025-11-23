<?php

declare(strict_types=1);

namespace App\CRM\Contact\Repository;

use App\CRM\Contact\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contact>
 */
class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    public function save(Contact $contact, bool $flush = true): void
    {
        $this->getEntityManager()->persist($contact);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Contact $contact, bool $flush = true): void
    {
        $this->getEntityManager()->remove($contact);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find contact by ID, excluding deleted
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
     * Find contacts by user ID
     *
     * @return Contact[]
     */
    public function findByUserId(int $userId, bool $includeDeleted = false): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.user_id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('c.created_at', 'DESC');

        if (!$includeDeleted) {
            $qb->andWhere('c.is_deleted = :deleted')
                ->setParameter('deleted', false);
        }

        return $qb->getQuery()->getResult();
    }
}
