<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures\Cached\Flights;

use LaravelUi5\OData\Edm\Container\FunctionImport;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\EdmFunction;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Fixtures\Models\Passenger;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\FunctionResolverInterface;
use LaravelUi5\OData\Service\Contracts\QueryPlanInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaBuilderInterface;

/**
 * Test fixture: DiscoveryFlightService alone in its directory, as odata:cache requires (one service per
 * directory, OP01). tests-fixtures/ holds many services and cannot be cached.
 */
final class CachedFlightService extends ODataService
{
    public function serviceUri(): string
    {
        return '';
    }

    public function namespace(): string
    {
        return 'Test.Ns';
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverModel(Flight::class);
        $this->discoverModel(Passenger::class);

        return $builder->namespace('Test.Ns');
    }
}
