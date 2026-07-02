<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;

/**
 * Test registry for the custom-query-option HTTP tests.
 */
final class CustomOptionServiceRegistry implements ODataServiceRegistryInterface
{
    private CustomOptionService $service;

    public function __construct()
    {
        $this->service = new CustomOptionService();
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
