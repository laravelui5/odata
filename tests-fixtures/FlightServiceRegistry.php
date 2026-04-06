<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;

/**
 * Test registry that always returns a FlightService.
 *
 * Bind this in setUp() (after provider boot) to route all OData requests
 * through the FlightService fixture:
 *
 *   $this->app->instance(
 *       ODataServiceRegistryInterface::class,
 *       new FlightServiceRegistry(),
 *   );
 */
final class FlightServiceRegistry implements ODataServiceRegistryInterface
{
    private FlightService $service;

    public function __construct()
    {
        $this->service = new FlightService();
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
