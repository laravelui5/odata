<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

/**
 * Resolves the active ODataService for an incoming request.
 *
 * The full path (including all path segments) is passed so implementations
 * can support multi-tenant or prefix-based routing strategies where multiple
 * services are mounted at different URI prefixes.
 *
 * @throws \RuntimeException if no service matches the given path
 */
interface ODataServiceRegistryInterface
{
    /**
     * @return ODataServiceInterface[]
     */
    public function services(): array;

    public function resolve(string $fullPath): ODataServiceInterface;
}
