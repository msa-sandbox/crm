<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Transformer;

use App\Api\V1\Transformer\LeadCoreTransformer;
use App\CRM\Contact\Entity\Contact;
use App\CRM\Lead\Entity\Lead;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class LeadCoreTransformerTest extends TestCase
{
    private function makeLead(int $id = 1): Lead
    {
        $lead = new Lead(
            title: 'Test Lead',
            status: 'open',
            pipelineStage: 'new',
            budget: '1000.00',
            accountId: 1,
            userId: 10,
            createdBy: 10,
            updatedBy: 10,
            id: $id,
            description: 'Description',
            notes: 'Note'
        );

        // Fake creation date
        $ref = new ReflectionClass($lead);
        foreach (['createdAt', 'updatedAt'] as $prop) {
            $property = $ref->getProperty($prop);
            $property->setAccessible(true);
            $property->setValue($lead, new DateTimeImmutable('2025-01-01 12:00:00'));
        }

        return $lead;
    }

    private function makeContact(int $id): Contact
    {
        $contact = new Contact(
            firstName: 'John',
            lastName: 'Doe',
            accountId: 1,
            userId: 10,
            createdBy: 10,
            updatedBy: 10,
            id: $id
        );

        $ref = new ReflectionClass($contact);
        foreach (['createdAt', 'updatedAt'] as $prop) {
            $property = $ref->getProperty($prop);
            $property->setAccessible(true);
            $property->setValue($contact, new DateTimeImmutable('2025-01-01 10:00:00'));
        }

        return $contact;
    }

    public function testTransformSingleLead(): void
    {
        $transformer = new LeadCoreTransformer();
        $lead = $this->makeLead(99);

        $result = $transformer->transform($lead);

        $this->assertSame(99, $result['id']);
        $this->assertSame('Test Lead', $result['title']);
        $this->assertSame('open', $result['status']);
        $this->assertSame(1000.0, $result['budget']);
        $this->assertSame('2025-01-01 12:00:00', $result['createdAt']);
    }

    public function testTransformCollection(): void
    {
        $transformer = new LeadCoreTransformer();
        $leads = [$this->makeLead(1), $this->makeLead(2)];
        $result = $transformer->transformCollection($leads);

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]['id']);
        $this->assertSame(2, $result[1]['id']);
    }

    public function testTransformCreateLeads(): void
    {
        $transformer = new LeadCoreTransformer();
        $result = $transformer->transformCreateLeads([$this->makeLead(7)]);

        $this->assertSame([['id' => 7]], $result);
    }

    public function testTransformCreateLeadsWithContacts(): void
    {
        $transformer = new LeadCoreTransformer();
        $lead = $this->makeLead(5);
        $contact1 = $this->makeContact(11);
        $contact2 = $this->makeContact(12);

        $lead->addContact($contact1);
        $lead->addContact($contact2);

        $result = $transformer->transformCreateLeadsWithContacts([$lead]);

        $this->assertSame(5, $result[0]['id']);
        $this->assertSame([['id' => 11], ['id' => 12]], $result[0]['contacts']);
    }
}
