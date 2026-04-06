<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

/**
 * Resolves a singleton to its single entity instance.
 *
 * Implementations return the singleton's current state as an associative
 * array. The singleton is always a single entity — no collection, no key.
 */
interface SingletonResolverInterface
{
    /**
     * @return array<string, mixed>  The singleton entity as an associative array.
     */
    public function resolve(): array;
}
