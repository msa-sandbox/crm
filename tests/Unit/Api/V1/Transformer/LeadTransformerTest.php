<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Transformer;

use App\Api\V1\Transformer\ContactCoreTransformer;
use App\Api\V1\Transformer\LeadCoreTransformer;
use App\Api\V1\Transformer\LeadTransformer;
use App\CRM\Contact\Entity\Contact;
use App\CRM\Lead\Entity\Lead;
use App\CRM\Lead\Enum\RelationsEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class LeadTransformerTest extends TestCase
{
    private function makeLead(int $id = 1): Lead
    {
        $lead = new Lead(
            title: 'Test Lead '.$id,
            status: 'open',
            pipelineStage: 'new',
            budget: '1500.00',
            accountId: 1,
            userId: 10,
            createdBy: 10,
            updatedBy: 10,
            id: $id,
            description: 'Desc '.$id,
            notes: 'Note '.$id
        );

        $ref = new ReflectionClass($lead);
        foreach (['createdAt', 'updatedAt'] as $prop) {
            $p = $ref->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue($lead, new DateTimeImmutable('2025-01-01 12:00:00'));
        }

        return $lead;
    }

    private function makeContact(int $id = 1): Contact
    {
        $contact = new Contact(
            firstName: 'John',
            lastName: 'Doe '.$id,
            accountId: 1,
            userId: 10,
            createdBy: 10,
            updatedBy: 10,
            id: $id
        );

        $ref = new ReflectionClass($contact);
        foreach (['createdAt', 'updatedAt'] as $prop) {
            $p = $ref->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue($contact, new DateTimeImmutable('2025-01-01 10:00:00'));
        }

        return $contact;
    }

    public function testTransformSingleLeadWithoutRelations(): void
    {
        $transformer = new LeadTransformer(
            new LeadCoreTransformer(),
            new ContactCoreTransformer()
        );

        $lead = $this->makeLead(100);
        $result = $transformer->transform($lead);

        $this->assertSame(100, $result['id']);
        $this->assertSame('Test Lead 100', $result['title']);
        $this->assertArrayNotHasKey('_embedded', $result);
    }

    public function testTransformSingleLeadWithContacts(): void
    {
        $transformer = new LeadTransformer(
            new LeadCoreTransformer(),
            new ContactCoreTransformer()
        );

        $lead = $this->makeLead(5);
        $contact1 = $this->makeContact(1);
        $contact2 = $this->makeContact(2);
        $lead->addContact($contact1);
        $lead->addContact($contact2);

        $result = $transformer->transform($lead, [RelationsEnum::CONTACTS->value]);

        $this->assertArrayHasKey('_embedded', $result);
        $this->assertArrayHasKey(RelationsEnum::CONTACTS->value, $result['_embedded']);
        $this->assertCount(2, $result['_embedded'][RelationsEnum::CONTACTS->value]);
        $this->assertSame(1, $result['_embedded'][RelationsEnum::CONTACTS->value][0]['id']);
    }

    public function testTransformCollection(): void
    {
        $transformer = new LeadTransformer(
            new LeadCoreTransformer(),
            new ContactCoreTransformer()
        );

        $leads = [$this->makeLead(1), $this->makeLead(2)];
        $result = $transformer->transformCollection($leads, limit: 10);

        $this->assertArrayHasKey('_meta', $result);
        $this->assertSame(10, $result['_meta']['limit']);
        $this->assertSame(2, $result['_meta']['next_after_id']);

        $this->assertArrayHasKey('leads', $result);
        $this->assertCount(2, $result['leads']);
        $this->assertSame(1, $result['leads'][0]['id']);
    }
}
