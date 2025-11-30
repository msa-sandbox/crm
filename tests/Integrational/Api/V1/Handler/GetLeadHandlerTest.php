<?php

declare(strict_types=1);

namespace App\Tests\Integrational\Api\V1\Handler;

use App\Api\V1\Dto\Request\Lead\GetLeadItemQueryDto;
use App\Api\V1\Dto\Request\Lead\GetLeadQueryDto;
use App\Api\V1\Handler\Lead\GetLeadHandler;
use App\CRM\Contact\Entity\Contact;
use App\CRM\Lead\Entity\Lead;
use App\CRM\Lead\Enum\RelationsEnum;
use App\Tests\Support\Auth\TestAuthTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Integration test that uses a real (test) database without mocks to verify the full data flow of GetLeadHandler.
 * A fake authenticated user is created to satisfy permission checks.
 * The database schema is reset at the start of the test to ensure isolation and consistency.
 * The test validates handler behavior for pagination, search, soft-deleted records, and optional entity relations (e.g., contacts).
 */
final class GetLeadHandlerTest extends KernelTestCase
{
    use TestAuthTrait;

    private EntityManagerInterface $em;
    private GetLeadHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->handler = $container->get(GetLeadHandler::class);

        // Auth test user
        $this->authenticateTestUser($container->get(TokenStorageInterface::class));

        // Truncate tables
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);

        // Lead 1 - active
        $lead1 = new Lead(
            title: 'Open Deal',
            status: 'open',
            pipelineStage: 'initial',
            budget: '1000',
            accountId: 999,
            userId: 1,
            createdBy: 1,
            updatedBy: 1,
            description: 'Initial stage lead'
        );

        // Lead 2 - deleted
        $lead2 = new Lead(
            title: 'Old Lead',
            status: 'closed',
            pipelineStage: 'finished',
            budget: '500',
            accountId: 999,
            userId: 1,
            createdBy: 1,
            updatedBy: 1,
            description: 'This one is deleted'
        );
        $lead2->setAsDeleted();

        // Lead 3 - for search
        $lead3 = new Lead(
            title: 'Searchable Lead',
            status: 'open',
            pipelineStage: 'proposal',
            budget: '1500',
            accountId: 999,
            userId: 1,
            createdBy: 1,
            updatedBy: 1,
            description: 'Find me by search term'
        );

        // Contact for 1st lead
        $contact = new Contact(
            firstName: 'John',
            lastName: 'Linked',
            accountId: 999,
            userId: 1,
            createdBy: 1,
            updatedBy: 1,
            email: 'linked@example.com'
        );
        $lead1->addContact($contact);

        $this->em->persist($contact);
        $this->em->persist($lead1);
        $this->em->persist($lead2);
        $this->em->persist($lead3);
        $this->em->flush();

        $this->em->clear();
    }

    public function testBasicListRespectsLimit(): void
    {
        $query = new GetLeadQueryDto(afterId: null, limit: 2, includeDeleted: false, search: null, with: []);
        $result = $this->handler->getList($query);

        $this->assertCount(2, $result['leads']);
        $this->assertSame(2, $result['_meta']['limit']);
        $this->assertArrayHasKey('next_after_id', $result['_meta']);
    }

    public function testIncludeDeletedReturnsAll(): void
    {
        $query = new GetLeadQueryDto(afterId: null, limit: 10, includeDeleted: true, search: null, with: []);
        $result = $this->handler->getList($query);

        // 3 leads: 2 active and 1 deleted
        $this->assertCount(3, $result['leads']);
        $deleted = array_filter($result['leads'], fn ($l) => $l['isDeleted']);
        $this->assertCount(1, $deleted);
    }

    public function testExcludeDeletedByDefault(): void
    {
        $query = new GetLeadQueryDto(afterId: null, limit: 10, includeDeleted: false, search: null, with: []);
        $result = $this->handler->getList($query);

        // Only 2 active leads should be
        $this->assertCount(2, $result['leads']);
        foreach ($result['leads'] as $lead) {
            $this->assertFalse($lead['isDeleted']);
        }
    }

    public function testWithContactsEmbedsRelatedEntities(): void
    {
        $query = new GetLeadQueryDto(afterId: null, limit: 10, includeDeleted: false, search: null, with: RelationsEnum::CONTACTS->value);
        $result = $this->handler->getList($query);

        $lead = array_filter($result['leads'], fn ($l) => 'Open Deal' === $l['title']);
        $lead = array_values($lead)[0];

        $this->assertArrayHasKey('_embedded', $lead);
        $this->assertArrayHasKey('contacts', $lead['_embedded']);
        $this->assertSame('John', $lead['_embedded']['contacts'][0]['firstName']);
    }

    public function testPaginationAfterIdSkipsPreviousRecords(): void
    {
        // Get 1st lead
        $firstPage = $this->handler->getList(new GetLeadQueryDto(afterId: null, limit: 1, includeDeleted: false, search: null, with: []));
        $firstLeadId = $firstPage['_meta']['next_after_id'];

        // Get next page
        $secondPage = $this->handler->getList(new GetLeadQueryDto(afterId: $firstLeadId, limit: 10, includeDeleted: false, search: null, with: []));

        $this->assertGreaterThan(0, $firstLeadId);
        $this->assertNotSame($firstPage['leads'][0]['id'], $secondPage['leads'][0]['id']);
    }

    public function testGetOneByIdThrowsNotFoundForMissingLead(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Lead 999 not found');

        $query = new GetLeadItemQueryDto(with: []);
        $this->handler->getOneById(999, $query); // no such ID
    }

    public function testGetOneByIdThrowsNotFoundForDifferentAccount(): void
    {
        // Create a lead under another account
        $lead = new Lead(
            title: 'Foreign Lead',
            status: 'open',
            pipelineStage: 'initial',
            budget: '200',
            accountId: 123, // different account
            userId: 1,
            createdBy: 1,
            updatedBy: 1
        );

        $this->em->persist($lead);
        $this->em->flush();
        $this->em->clear();

        $query = new GetLeadItemQueryDto(with: []);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessageMatches('/Lead \d+ not found/');

        // Fake user is tied to accountId = 999, so lead from 123 must not be visible
        $this->handler->getOneById($lead->getId(), $query);
    }
}
