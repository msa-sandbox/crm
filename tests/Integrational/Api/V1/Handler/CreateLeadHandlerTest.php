<?php

declare(strict_types=1);

namespace App\Tests\Integrational\Api\V1\Handler;

use App\Api\V1\Dto\Request\Contact\CreateContactCollection;
use App\Api\V1\Dto\Request\Contact\CreateContactDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadCollectionDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadWithContactCollectionDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadWithContactDto;
use App\Api\V1\Handler\Lead\CreateLeadHandler;
use App\CRM\Contact\Entity\Contact;
use App\CRM\Lead\Entity\Lead;
use App\Exception\TransactionException;
use App\Tests\Support\Auth\TestAuthTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Integration test verifying CreateLeadHandler end-to-end behavior with the real database.
 * Covers:
 *  - creating standalone leads,
 *  - creating leads with related contacts (POST /leads/complex),
 *  - rollback behavior on database error.
 * Uses a fake authenticated user and resets DB schema before each test.
 */
final class CreateLeadHandlerTest extends KernelTestCase
{
    use TestAuthTrait;

    private EntityManagerInterface $em;
    private CreateLeadHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->handler = $container->get(CreateLeadHandler::class);

        $this->authenticateTestUser($container->get(TokenStorageInterface::class));

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);
    }

    public function testCreatesSimpleLeads(): void
    {
        $dto1 = new CreateLeadDto(
            title: 'Website redesign',
            status: 'open',
            pipelineStage: 'initial',
            budget: 5000,
            description: 'Landing page redesign project',
            notes: 'Test lead 1'
        );

        $dto2 = new CreateLeadDto(
            title: 'CRM implementation',
            status: 'open',
            pipelineStage: 'proposal',
            budget: 10000,
            description: 'Integrate CRM system',
            notes: 'Test lead 2'
        );

        $collection = new CreateLeadCollectionDto([$dto1, $dto2]);

        $result = $this->handler->createBulk($collection);

        // transformer returns only IDs
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertIsInt($result[0]['id']);

        $leads = $this->em->getRepository(Lead::class)->findAll();
        $this->assertCount(2, $leads);
    }

    public function testCreatesLeadsWithContacts(): void
    {
        // Prepare embedded contact DTOs
        $contact1 = new CreateContactDto(
            firstName: 'Alice',
            lastName: 'Johnson',
            email: 'alice@example.com',
            phone: '+123456789',
            company: 'Acme Corp',
            city: 'NY',
            country: 'USA',
            notes: 'First contact'
        );

        $contact2 = new CreateContactDto(
            firstName: 'Bob',
            lastName: 'Smith',
            email: 'bob@example.com',
            phone: '+987654321',
            company: 'Techify Ltd',
            city: 'Berlin',
            country: 'Germany',
            notes: 'Second contact'
        );

        $leadDto = new CreateLeadWithContactDto(
            title: 'Enterprise deal',
            status: 'open',
            pipelineStage: 'proposal',
            budget: 15000,
            description: 'Big contract with multiple contacts',
            notes: 'Urgent',
            _embedded: new CreateContactCollection([$contact1, $contact2])
        );

        $collection = new CreateLeadWithContactCollectionDto([$leadDto]);

        $result = $this->handler->createBulkWithContacts($collection);

        // Expect structure like:
        // [
        //   ['id' => 1, 'contacts' => [['id'=>..], ['id'=>..]]]
        // ]
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('contacts', $result[0]);
        $this->assertCount(2, $result[0]['contacts']);

        // Verify in DB
        $lead = $this->em->getRepository(Lead::class)->find($result[0]['id']);
        $this->assertCount(2, $lead->getContacts());
    }

    public function testTransactionRollbackOnDatabaseError(): void
    {
        // Intentionally trigger DB-level error by using invalid (too long) title
        $invalidTitle = str_repeat('A', 300); // exceeds DB field length

        $leadDto = new CreateLeadDto(
            title: $invalidTitle,
            status: 'open',
            pipelineStage: 'initial',
            budget: 1000,
            description: 'This will break DB constraints',
            notes: 'Expect rollback'
        );

        $collection = new CreateLeadCollectionDto([$leadDto]);

        $this->expectException(TransactionException::class);

        try {
            $this->handler->createBulk($collection);
        } catch (TransactionException $e) {
            // DB should be empty — rollback successful
            $leads = $this->em->getRepository(Lead::class)->findAll();
            $this->assertCount(0, $leads);
            $contacts = $this->em->getRepository(Contact::class)->findAll();
            $this->assertCount(0, $contacts);
            throw $e;
        }
    }
}
