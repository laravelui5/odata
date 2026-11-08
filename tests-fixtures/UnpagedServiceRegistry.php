<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;

/** Test registry for the unpaged-set HTTP tests (ROADMAP OP48). */
final class UnpagedServiceRegistry implements ODataServiceRegistryInterface
{
    private UnpagedService $service;

    public function __construct()
    {
        $this->service = new UnpagedService();
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
