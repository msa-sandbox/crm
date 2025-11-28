<?php

declare(strict_types=1);

namespace App\Api\V1\Dto;

/**
 * Contract for collection-style DTOs used in API request/response layers.
 *
 * DTOs implementing this interface represent typed collections of other DTO objects
 * (e.g., a list of leads, contacts, or tasks) that can be automatically denormalized
 * from JSON arrays by custom collection denormalizers.
 *
 * Responsibilities:
 *  - Define which class each collection item should be deserialized into
 *  - Optionally specify the property name that holds the collection items
 *
 * Used by:
 *  - DtoCollectionDenormalizer to automatically map JSON arrays into DTO collections
 *  - Nested DTOs that contain lists of other DTOs
 *
 * Example:
 *   class LeadCollectionDto implements DtoCollectionInterface {
 *       public static function getItemClass(): string {
 *           return LeadDto::class;
 *       }
 *
 *       public static function getItemsProperty(): string {
 *           return 'leads';
 *       }
 *   }
 */

interface DtoCollectionInterface
{
    /**
     * Returns the fully qualified class name of the DTO type
     * contained within the collection.
     *
     * Used by the denormalizer to know which class each element
     * of the JSON array should be converted into.
     *
     * @return string
     */
    public static function getItemClass(): string;

    /**
     * Returns the name of the property that holds
     * the collection items within the DTO.
     *
     * Used when the collection is wrapped in an associative
     * structure (e.g. {"contacts": [...]}) to identify which
     * key contains the list of items.
     *
     * @return string
     */
    public static function getItemsProperty(): string;

    /**
     * @return array
     */
    public function all(): array;
}
