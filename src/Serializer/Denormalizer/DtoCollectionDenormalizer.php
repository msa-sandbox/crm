<?php

declare(strict_types=1);

namespace App\Serializer\Denormalizer;

use App\Api\V1\Dto\DtoCollectionInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Custom denormalizer for DTO collection classes implementing DtoCollectionInterface.
 *
 * This denormalizer allows Symfony’s Serializer component to handle JSON arrays
 * and convert them into strongly typed collection DTOs (e.g., LeadCollectionDto, ContactsCollectionDto).
 *
 * Key features:
 *  - Automatically maps JSON arrays to collection DTOs that implement DtoCollectionInterface
 *  - Uses the collection’s static getItemClass() method to determine the element type
 *  - Recursively delegates denormalization of individual items to Symfony’s serializer
 *  - Prevents infinite recursion via type-specific context flags
 *
 * Example:
 *     JSON:
 *     [
 *       { "title": "Lead A", "status": "active" },
 *       { "title": "Lead B", "status": "won" }
 *     ]
 *
 *     $serializer->deserialize($json, CreateLeadCollectionDto::class, 'json');
 * -> returns CreateLeadCollectionDto with an array of CreateLeadDto objects
 *
 * Typical usage:
 *  - Works seamlessly with #[MapRequestPayload] in controllers
 *  - Supports both root-level and nested DTO collections
 *
 * @implements DenormalizerInterface<object>
 */
class DtoCollectionDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    /**
     * @template T of DtoCollectionInterface
     *
     * @param mixed $data
     * @param class-string<T> $type
     * @param string|null $format
     * @param array $context
     *
     * @return T
     *
     * @throws ExceptionInterface
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $context[self::class] = true;

        $itemClass = $type::getItemClass();

        $items = [];
        foreach ($data as $item) {
            $items[] = $this->denormalizer->denormalize($item, $itemClass, $format, $context);
        }

        return new $type($items);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data)
            && !isset($context[self::class])
            && is_subclass_of($type, DtoCollectionInterface::class);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            DtoCollectionInterface::class => true,
        ];
    }
}
