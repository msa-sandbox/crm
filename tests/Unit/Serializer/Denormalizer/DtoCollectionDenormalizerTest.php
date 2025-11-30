<?php

declare(strict_types=1);

namespace App\Tests\Unit\Serializer\Denormalizer;

use App\Api\V1\Dto\DtoCollectionInterface;
use App\Serializer\Denormalizer\DtoCollectionDenormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * This unit test verifies the behavior of `DtoCollectionDenormalizer`, which converts raw array data into typed DTO collection objects.
 *
 * It checks that:
 *  - The denormalizer correctly supports only classes implementing `DtoCollectionInterface`.
 *  - The context flag prevents recursive denormalization.
 *  - Each array element is delegated to the inner Symfony denormalizer.
 *  - The resulting object is a typed collection containing properly denormalized DTOs.
 *
 * The test uses mock objects for `DenormalizerInterface` and ensures compatibility with PHPUnit 10+ (using `willReturnCallback()` instead of `withConsecutive()`).
 */
final class DtoCollectionDenormalizerTest extends TestCase
{
    private DtoCollectionDenormalizer $denormalizer;
    private DenormalizerInterface&MockObject $innerDenormalizer;

    protected function setUp(): void
    {
        $this->innerDenormalizer = $this->createMock(DenormalizerInterface::class);
        $this->denormalizer = new DtoCollectionDenormalizer();
        $this->denormalizer->setDenormalizer($this->innerDenormalizer);
    }

    public function testSupportsDenormalizationReturnsTrueForDtoCollections(): void
    {
        $result = $this->denormalizer->supportsDenormalization(
            data: [['a' => 1]],
            type: DummyCollection::class
        );

        $this->assertTrue($result);
    }

    public function testSupportsDenormalizationReturnsFalseForNonCollections(): void
    {
        $result = $this->denormalizer->supportsDenormalization(
            data: [['a' => 1]],
            type: stdClass::class
        );

        $this->assertFalse($result);
    }

    public function testSupportsDenormalizationReturnsFalseWhenContextHasFlag(): void
    {
        $context = [DtoCollectionDenormalizer::class => true];

        $result = $this->denormalizer->supportsDenormalization(
            data: [['a' => 1]],
            type: DummyCollection::class,
            context: $context
        );

        $this->assertFalse($result);
    }

    public function testDenormalizeCreatesCollectionOfDenormalizedItems(): void
    {
        $inputData = [
            ['id' => 1],
            ['id' => 2],
        ];

        $expectedObjects = [
            new DummyDto(1),
            new DummyDto(2),
        ];

        // Set up mock to return expected objects
        $this->innerDenormalizer
            ->expects($this->exactly(2))
            ->method('denormalize')
            ->willReturnCallback(function ($data, $class, $format, $context) use (&$callIndex, $expectedObjects) {
                $this->assertTrue(isset($context[DtoCollectionDenormalizer::class]));
                static $i = 0;

                return $expectedObjects[$i++];
            });

        /** @var DummyCollection $collection */
        $collection = $this->denormalizer->denormalize($inputData, DummyCollection::class);

        $this->assertInstanceOf(DummyCollection::class, $collection);
        $this->assertEquals($expectedObjects, $collection->all());
    }
}

/**
 * Dummy DTO for testing.
 */
final class DummyDto
{
    public function __construct(public int $id)
    {
    }
}

/**
 * Dummy DTO collection compatible with DtoCollectionInterface.
 */
final class DummyCollection implements DtoCollectionInterface
{
    public function __construct(private array $items)
    {
    }

    public static function getItemClass(): string
    {
        return DummyDto::class;
    }

    public static function getItemsProperty(): string
    {
        return 'items';
    }

    public function all(): array
    {
        return $this->items;
    }
}
