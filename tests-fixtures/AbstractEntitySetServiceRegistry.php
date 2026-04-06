<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;

/**
 * Test registry for AbstractEntitySet HTTP tests.
 */
final class AbstractEntitySetServiceRegistry implements ODataServiceRegistryInterface
{
    private AbstractEntitySetService $service;

    public function __construct()
    {
        $this->service = new AbstractEntitySetService();
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
