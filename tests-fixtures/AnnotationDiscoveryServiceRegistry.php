<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;

/**
 * Test registry for annotation discovery HTTP tests.
 */
final class AnnotationDiscoveryServiceRegistry implements ODataServiceRegistryInterface
{
    private AnnotationDiscoveryService $service;

    public function __construct()
    {
        $this->service = new AnnotationDiscoveryService();
    }

    public function resolve(string $fullPath): ODataServiceInterface
    {
        return $this->service;
    }

    public function services(): array
    {
        return [$this->service];
    }
}
