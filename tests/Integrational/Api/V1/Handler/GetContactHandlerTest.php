<?php

declare(strict_types=1);

namespace App\Tests\Integrational\Api\V1\Handler;

use App\Api\V1\Dto\Request\Contact\GetContactItemQueryDto;
use App\Api\V1\Dto\Request\Contact\GetContactQueryDto;
use App\Api\V1\Handler\Contact\GetContactHandler;
use App\CRM\Contact\Entity\Contact;
use App\CRM\Contact\Enum\RelationsEnum;
use App\CRM\Lead\Entity\Lead;
use App\Tests\Support\Auth\TestAuthTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Integration test that uses a real (test) database without mocks to verify the full data flow of GetContactHandler.
 * A fake authenticated user is created to satisfy permission checks.
 * The database schema is reset at the start of the test to ensure isolation and consistency.
 * The test validates handler behavior for pagination, search, soft-deleted records, and optional entity relations (e.g., leads).
 */
final class GetContactHandlerTest extends KernelTestCase
{
    use TestAuthTrait;

    private EntityManagerInterface $em;
    private GetContactHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->handler = $container->get(GetContactHandler::class);

        // Auth test user
        $this->authenticateTestUser($container->get(TokenStorageInterface::class));

        // Truncate tables
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($metadata);

        // Contact 1 - active
        $contact1 = new Contact(
            firstName: 'John',
            lastName: 'Doe',
            accountId: 999,
            userId: 1,
            createdBy: 1,
            updatedBy: 1,
            email: 'john@example.com'
        );

        // Contact 2 -- deleted
        $contact2 = new Contact(
            firstName: 'Jane',
            lastName: 'Deleted',
            accountId: 999,
            userId: 1,
            createdBy: 1,
            updatedBy: 1,
            isDeleted: true,
            email: 'deleted@example.com'
        );

        // Contact 3 for search
        $contact3 = new Contact(
            firstName: 'Bob',
            lastName: 'Search',
            accountId: 999,
            userId: 1,
            createdBy: 1,
            updatedBy: 1,
            email: 'bob.search@example.com'
        );

        // Lead for 1st contact
        $lead = new Lead(
            title: 'Test Lead',
            status: 'open',
            pipelineStage: 'initial',
            budget: '500',
            accountId: 999,
            userId: 1,
            createdBy: 1,
            updatedBy: 1
        );
        $lead->addContact($contact1);

        $this->em->persist($contact1);
        $this->em->persist($contact2);
        $this->em->persist($contact3);
        $this->em->persist($lead);
        $this->em->flush();

        $this->em->clear();
    }

    public function testBasicListRespectsLimit(): void
    {
        $query = new GetContactQueryDto(afterId: null, limit: 2, includeDeleted: false, search: null, with: []);
        $result = $this->handler->getList($query);

        $this->assertCount(2, $result['contacts']);
        $this->assertSame(2, $result['_meta']['limit']);
        $this->assertArrayHasKey('next_after_id', $result['_meta']);
    }

    public function testSearchFiltersByEmail(): void
    {
        $query = new GetContactQueryDto(afterId: null, limit: 10, includeDeleted: false, search: 'search', with: []);
        $result = $this->handler->getList($query);

        $this->assertCount(1, $result['contacts']);
        $this->assertSame('Bob', $result['contacts'][0]['firstName']);
    }

    public function testIncludeDeletedReturnsAll(): void
    {
        $query = new GetContactQueryDto(afterId: null, limit: 10, includeDeleted: true, search: null, with: []);
        $result = $this->handler->getList($query);

        // 3 contacts: 2 active and 1 deleted
        $this->assertCount(3, $result['contacts']);
        $deleted = array_filter($result['contacts'], fn ($c) => $c['isDeleted']);
        $this->assertCount(1, $deleted);
    }

    public function testExcludeDeletedByDefault(): void
    {
        $query = new GetContactQueryDto(afterId: null, limit: 10, includeDeleted: false, search: null, with: []);
        $result = $this->handler->getList($query);

        // Only 2 active contacts should be
        $this->assertCount(2, $result['contacts']);
        foreach ($result['contacts'] as $contact) {
            $this->assertFalse($contact['isDeleted']);
        }
    }

    public function testWithLeadsEmbedsRelatedEntities(): void
    {
        $query = new GetContactQueryDto(afterId: null, limit: 10, includeDeleted: false, search: null, with: RelationsEnum::LEADS->value);
        $result = $this->handler->getList($query);

        $john = array_filter($result['contacts'], fn ($c) => 'John' === $c['firstName']);
        $john = array_values($john)[0];

        $this->assertArrayHasKey('_embedded', $john);
        $this->assertArrayHasKey('leads', $john['_embedded']);
        $this->assertSame('Test Lead', $john['_embedded']['leads'][0]['title']);
    }

    public function testPaginationAfterIdSkipsPreviousRecords(): void
    {
        // Get 1st contact
        $firstPage = $this->handler->getList(new GetContactQueryDto(afterId: null, limit: 1, includeDeleted: false, search: null, with: []));
        $firstContactId = $firstPage['_meta']['next_after_id'];

        // Get next page
        $secondPage = $this->handler->getList(new GetContactQueryDto(afterId: $firstContactId, limit: 10, includeDeleted: false, search: null, with: []));

        $this->assertGreaterThan(0, $firstContactId);
        $this->assertNotSame($firstPage['contacts'][0]['id'], $secondPage['contacts'][0]['id']);
    }

    public function testGetOneByIdThrowsNotFoundForMissingContact(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Contact 999 not found');

        $query = new GetContactItemQueryDto(with: []);
        $this->handler->getOneById(999, $query); // no such contact
    }

    public function testGetOneByIdThrowsNotFoundForDifferentAccount(): void
    {
        // Create a contact for another account
        $foreign = new Contact(
            firstName: 'Alice',
            lastName: 'Foreign',
            accountId: 123, // not 999
            userId: 1,
            createdBy: 1,
            updatedBy: 1,
            email: 'foreign@example.com'
        );

        $this->em->persist($foreign);
        $this->em->flush();
        $this->em->clear();

        $query = new GetContactItemQueryDto(with: []);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessageMatches('/Contact \d+ not found/');

        // Handler should not return contact from another account
        $this->handler->getOneById($foreign->getId(), $query);
    }
}
