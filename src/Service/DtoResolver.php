<?php

declare(strict_types=1);

namespace App\Service;

use LogicException;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

/**
 * Resolves and instantiates API DTOs from raw request data.
 *
 * This resolver provides a unified way to build and validate DTO objects
 * directly from API request payloads: both single objects
 * and arrays of objects (bulk operations).
 *
 * Core use cases:
 *  - Creating request DTOs from JSON input in controllers
 *  - Validating DTOs automatically using Symfony Validator
 *  - Supporting bulk creation (arrays of DTOs) and single-object payloads
 *
 * Key features:
 *  - Uses ReflectionClass to instantiate readonly, constructor-based DTOs
 *  - Automatically maps input keys to constructor argument names
 *  - Performs Symfony validation immediately after instantiation
 *  - Supports both list (bulk) and associative (single) payloads
 *  - Keeps DTOs immutable and cleanly separated from request structures
 *
 * Notes:
 *  - DTO classes must have an explicit constructor with named arguments
 *  - Constructor parameter names must match keys in the input array
 *  - Works seamlessly with Symfony Validator constraints defined on constructor arguments
 *
 * Example flow:
 *   - Controller receives JSON request
 *   - Calls DtoResolver::resolve(CreateLeadDto::class, $request->toArray())
 *   - Resolver returns a validated CreateLeadDto instance (or an array of them)
 *   - Controller passes DTO(s) to service layer for further processing
 */
readonly class DtoResolver
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * Validates and instantiates DTOs from raw request data.
     * We always want to get an array of dtos as a result to have only 1 logic.
     *
     * @template T of object
     *
     * @param class-string<T> $dtoClass
     * @param array $data
     *
     * @return T[]
     */
    public function resolve(string $dtoClass, array $data): array
    {
        // in the case of bulk operation, build an array of DTOs
        if (!array_is_list($data)) {
            $data = [$data];
        }

        // standalone DTO
        return array_map(fn ($item) => $this->build($dtoClass, $item), $data);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $dtoClass
     * @param array $input
     *
     * @return T
     */
    private function build(string $dtoClass, array $input): object
    {
        try {
            $reflection = new ReflectionClass($dtoClass);
            $constructor = $reflection->getConstructor();

            $args = [];
            foreach ($constructor?->getParameters() ?? [] as $param) {
                $name = $param->getName();
                $args[] = $input[$name] ?? ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);
            }

            $dto = $reflection->newInstanceArgs($args);

            $violations = $this->validator->validate($dto);
            if (count($violations) > 0) {
                // We are using this DtoResolver only for API requests, so we can throw a BadRequestException
                throw new BadRequestException((string) $violations);
            }

            return $dto;
        } catch (Throwable $e) {
            throw new LogicException(sprintf('Failed to build DTO %s: %s', $dtoClass, $e->getMessage()), 0, $e);
        }
    }
}
