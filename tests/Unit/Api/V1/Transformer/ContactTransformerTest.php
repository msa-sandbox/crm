<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Transformer;

use App\Api\V1\Transformer\ContactCoreTransformer;
use App\Api\V1\Transformer\ContactTransformer;
use App\Api\V1\Transformer\LeadCoreTransformer;
use App\CRM\Contact\Entity\Contact;
use App\CRM\Contact\Enum\RelationsEnum;
use App\CRM\Lead\Entity\Lead;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ContactTransformerTest extends TestCase
{
    private function makeContact(int $id = 1): Contact
    {
        $contact = new Contact(
            firstName: 'John',
            lastName: 'Smith '.$id,
            accountId: 1,
            userId: 10,
            createdBy: 10,
            updatedBy: 10,
            id: $id,
            isDeleted: false,
            email: 'john'.$id.'@example.com',
            phone: '+123',
            company: 'Acme',
            city: 'NY',
            country: 'US',
            notes: 'Note'
        );

        $ref = new ReflectionClass($contact);
        foreach (['createdAt', 'updatedAt'] as $prop) {
            $p = $ref->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue($contact, new DateTimeImmutable('2025-01-01 09:00:00'));
        }

        return $contact;
    }

    private function makeLead(int $id): Lead
    {
        $lead = new Lead(
            title: 'Lead '.$id,
            status: 'open',
            pipelineStage: 'new',
            budget: '200.00',
            accountId: 1,
            userId: 10,
            createdBy: 10,
            updatedBy: 10,
            id: $id,
            description: 'Description',
            notes: 'Notes'
        );

        $ref = new ReflectionClass($lead);
        foreach (['createdAt', 'updatedAt'] as $prop) {
            $p = $ref->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue($lead, new DateTimeImmutable('2025-01-01 11:00:00'));
        }

        return $lead;
    }

    public function testTransformSingleContactWithoutRelations(): void
    {
        $transformer = new ContactTransformer(
            new ContactCoreTransformer(),
            new LeadCoreTransformer()
        );

        $contact = $this->makeContact(50);
        $result = $transformer->transform($contact);

        $this->assertSame(50, $result['id']);
        $this->assertSame('John', $result['firstName']);
        $this->assertArrayNotHasKey('_embedded', $result);
    }

    public function testTransformSingleContactWithLeads(): void
    {
        $transformer = new ContactTransformer(
            new ContactCoreTransformer(),
            new LeadCoreTransformer()
        );

        $contact = $this->makeContact(5);
        $lead1 = $this->makeLead(10);
        $lead2 = $this->makeLead(11);
        $contact->addLead($lead1);
        $contact->addLead($lead2);

        $result = $transformer->transform($contact, [RelationsEnum::LEADS->value]);

        $this->assertArrayHasKey('_embedded', $result);
        $this->assertArrayHasKey(RelationsEnum::LEADS->value, $result['_embedded']);
        $this->assertCount(2, $result['_embedded'][RelationsEnum::LEADS->value]);
        $this->assertSame(10, $result['_embedded'][RelationsEnum::LEADS->value][0]['id']);
    }

    public function testTransformCollection(): void
    {
        $transformer = new ContactTransformer(
            new ContactCoreTransformer(),
            new LeadCoreTransformer()
        );

        $contacts = [$this->makeContact(1), $this->makeContact(2)];
        $result = $transformer->transformCollection($contacts, limit: 15);

        $this->assertArrayHasKey('_meta', $result);
        $this->assertSame(15, $result['_meta']['limit']);
        $this->assertSame(2, $result['_meta']['next_after_id']);

        $this->assertArrayHasKey('contacts', $result);
        $this->assertCount(2, $result['contacts']);
        $this->assertSame(1, $result['contacts'][0]['id']);
    }
}
