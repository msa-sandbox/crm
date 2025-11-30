<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\V1\Transformer;

use App\Api\V1\Transformer\ContactCoreTransformer;
use App\CRM\Contact\Entity\Contact;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ContactCoreTransformerTest extends TestCase
{
    private function makeContact(int $id = 1): Contact
    {
        $contact = new Contact(
            firstName: 'John',
            lastName: 'Doe',
            accountId: 1,
            userId: 10,
            createdBy: 10,
            updatedBy: 10,
            id: $id,
            isDeleted: false,
            email: 'john@example.com',
            phone: '+123',
            company: 'Acme',
            city: 'New York',
            country: 'USA',
            notes: 'Important client'
        );

        // Fake creation date
        $ref = new ReflectionClass($contact);
        foreach (['createdAt', 'updatedAt'] as $prop) {
            $property = $ref->getProperty($prop);
            $property->setAccessible(true);
            $property->setValue($contact, new DateTimeImmutable('2025-01-01 10:00:00'));
        }

        return $contact;
    }

    public function testTransformSingleContact(): void
    {
        $transformer = new ContactCoreTransformer();
        $contact = $this->makeContact(10);

        $result = $transformer->transform($contact);

        $this->assertSame(10, $result['id']);
        $this->assertSame('John', $result['firstName']);
        $this->assertSame('Doe', $result['lastName']);
        $this->assertSame('john@example.com', $result['email']);
        $this->assertSame('Acme', $result['company']);
        $this->assertFalse($result['isDeleted']);
        $this->assertSame('2025-01-01 10:00:00', $result['createdAt']);
    }

    public function testTransformCollection(): void
    {
        $transformer = new ContactCoreTransformer();
        $contacts = [$this->makeContact(1), $this->makeContact(2)];
        $result = $transformer->transformCollection($contacts);

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]['id']);
        $this->assertSame(2, $result[1]['id']);
    }

    public function testTransformCreateContacts(): void
    {
        $transformer = new ContactCoreTransformer();
        $result = $transformer->transformCreateContacts([$this->makeContact(5)]);
        $this->assertSame([['id' => 5]], $result);
    }
}
