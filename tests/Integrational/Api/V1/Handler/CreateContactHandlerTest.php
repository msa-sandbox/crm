<?php

declare(strict_types=1);

namespace App\Tests\Integrational\Api\V1\Handler;

use App\Api\V1\Dto\Request\Contact\CreateContactCollection;
use App\Api\V1\Dto\Request\Contact\CreateContactDto;
use App\Api\V1\Handler\Contact\CreateContactHandler;
use App\CRM\Contact\Entity\Contact;
use App\Exception\TransactionException;
use App\Tests\Support\Auth\TestAuthTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Integration test that verifies the full flow of contact creation using CreateContactHandler.
 * Uses the real (test) database, no mocks.
 * A fake authenticated user is created to satisfy permission checks.
 * The DB schema is reset before each test for isolation and consistency.
 * Tests cover creation, duplicate handling (email uniqueness), and transactional behavior.
 */
final class CreateContactHandlerTest extends KernelTestCase
{
    use TestAuthTrait;

    private EntityManagerInterface $em;
    private CreateContactHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->handler = $container->get(CreateContactHandler::class);

        // Auth fake user
        $this->authenticateTestUser($container->get(TokenStorageInterface::class));

        // Clean DB
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    public function testCreatesNewContacts(): void
    {
        $dto1 = new CreateContactDto(
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            phone: '+123456789',
            company: 'Example Inc',
            city: 'London',
            country: 'UK',
            notes: 'Test contact'
        );

        $dto2 = new CreateContactDto(
            firstName: 'Jane',
            lastName: 'Smith',
            email: 'jane@example.com',
            phone: '+987654321',
            company: 'Acme Ltd',
            city: 'Berlin',
            country: 'Germany',
            notes: 'Another one'
        );

        $collection = new CreateContactCollection([$dto1, $dto2]);

        $result = $this->handler->createBulk($collection);

        // transformer returns only IDs
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('id', $result[1]);
        $this->assertIsInt($result[0]['id']);
        $this->assertIsInt($result[1]['id']);

        $contacts = $this->em->getRepository(Contact::class)->findAll();
        $this->assertCount(2, $contacts);
    }

    public function testDuplicateEmailUpdatesExistingContact(): void
    {
        // First insert
        $first = new Contact(
            firstName: 'Old',
            lastName: 'User',
            accountId: 999,
            userId: 1,
            createdBy: 1,
            updatedBy: 1,
            email: 'update@example.com'
        );
        $this->em->persist($first);
        $this->em->flush();

        $existingId = $first->getId();

        $this->em->clear();

        // Payload with same email, but updated data
        $dto = new CreateContactDto(
            firstName: 'Updated',
            lastName: 'User',
            email: 'update@example.com',
            phone: '+111111111',
            company: 'New Corp',
            city: 'Paris',
            country: 'France',
            notes: 'Changed data'
        );
        $collection = new CreateContactCollection([$dto]);

        $result = $this->handler->createBulk($collection);

        // transformer returns only IDs, must be the same as existing contact
        $this->assertCount(1, $result);
        $this->assertSame($existingId, $result[0]['id']);

        /** @var Contact $contact */
        $contact = $this->em->getRepository(Contact::class)
            ->findOneBy(['email' => 'update@example.com']);

        $this->assertSame('Updated', $contact->getFirstName());
        $this->assertSame('New Corp', $contact->getCompany());
        $this->assertSame('Paris', $contact->getCity());
    }

    public function testThrowsOnDuplicateEmailsInPayload(): void
    {
        $dto1 = new CreateContactDto(
            firstName: 'John',
            lastName: 'Doe',
            email: 'dup@example.com'
        );

        $dto2 = new CreateContactDto(
            firstName: 'Jane',
            lastName: 'Smith',
            email: 'dup@example.com' // duplicate in payload
        );

        $collection = new CreateContactCollection([$dto1, $dto2]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate email in request: dup@example.com');

        $this->handler->createBulk($collection);
    }

    public function testTransactionRollbackOnDatabaseError(): void
    {
        $invalidEmail = str_repeat('a', 300).'@example.com'; // Much longer than allowed by DB schema
        $dto = new CreateContactDto(
            firstName: 'Fail',
            lastName: 'Case',
            email: $invalidEmail
        );

        $collection = new CreateContactCollection([$dto]);

        $this->expectException(TransactionException::class);

        try {
            $this->handler->createBulk($collection);
        } catch (TransactionException $e) {
            // Verify rollback effect: nothing persisted
            $contacts = $this->em->getRepository(Contact::class)->findAll();
            $this->assertCount(0, $contacts);
            throw $e;
        }
    }
}
