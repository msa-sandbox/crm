<?php

declare(strict_types=1);

namespace App\CRM\Contact\Repository;

use App\CRM\Contact\Contract\ContactRepositoryInterface;
use App\CRM\Contact\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contact>
 */
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
}
