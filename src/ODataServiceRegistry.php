<?php

declare(strict_types=1);

namespace LaravelUi5\OData;

use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;

/**
 * Default resolver: ignores the path and returns the single registered ODataService.
 * Adapters can bind their own ODataServiceRegistryInterface implementation to support
 * multi-tenant or prefix-based routing strategies.
 */
class ODataServiceRegistry implements ODataServiceRegistryInterface
{
    private ?ODataService $service = null;

    public function resolve(string $fullPath): ODataServiceInterface
    {
        if (!$this->service) {
            $this->service = new ODataService('', config('odata.namespace', 'com.example.odata'));
        }

        return $this->service;
    }

    public function services(): array
    {
        return [$this->resolve('')];
    }
}
