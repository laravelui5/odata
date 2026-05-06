<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use LaravelUi5\OData\Edm\Container\FunctionImport;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\EdmFunction;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\FunctionResolverInterface;
use LaravelUi5\OData\Service\Contracts\QueryPlanInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaBuilderInterface;

/**
 * Test fixture: discovered models coexisting with manually defined functions.
 */
final class DiscoveryWithManualService extends ODataService
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

        $int32 = new PrimitiveType(EdmPrimitiveType::Int32);
        $countFunc = new EdmFunction(name: 'GetCount', returnType: $int32);
        $countImport = new FunctionImport('GetCount', $countFunc);

        return $builder
            ->namespace('Test.Ns')
            ->addFunction($countFunc)
            ->addFunctionImport($countImport);
    }

    protected function bindFunctions(RuntimeSchemaBuilderInterface $builder): void
    {
        $container = $builder->getEdmx()->getEntityContainer();
        $builder->bindFunctionImport(
            $container->getFunctionImport('GetCount'),
            new class implements FunctionResolverInterface {
                public function resolve(QueryPlanInterface $plan): mixed
                {
                    return 42;
                }
            },
        );
    }
}
