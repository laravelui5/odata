<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

use LaravelUi5\OData\Http\ODataResponse;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Protocol\Planning\ServiceDocumentQueryPlan;

/**
 * Handles ServiceDocumentQueryPlan — produces the OData service document JSON.
 *
 * Emits:
 *   {"@odata.context":"<serviceRoot>$metadata","value":[
 *     {"name":"Products","kind":"EntitySet","url":"Products"},
 *     {"name":"Me","kind":"Singleton","url":"Me"},
 *     {"name":"GetCount","kind":"FunctionImport","url":"GetCount"},
 *     ...
 *   ]}
 *
 * An entity set or function import whose `IncludeInServiceDocument` is false is left out; a singleton has no such
 * flag and is always listed.
 */
final readonly class ServiceDocumentHandler
{
    public function __construct(private string $serviceRoot) {}

    public function handle(ServiceDocumentQueryPlan $plan): ODataResponse
    {
        $context  = $this->serviceRoot . '$metadata';
        $container = $plan->edmx->getEntityContainer();

        $entries = [];

        foreach ($container->getEntitySets() as $set) {
            /** @var EntitySetInterface $set */
            if ($set->isIncludedInServiceDocument()) {
                $entries[] = ['name' => $set->getName(), 'kind' => 'EntitySet', 'url' => $set->getName()];
            }
        }

        // A singleton carries no IncludeInServiceDocument flag: it is always listed.
        foreach ($container->getSingletons() as $singleton) {
            /** @var SingletonInterface $singleton */
            $entries[] = ['name' => $singleton->getName(), 'kind' => 'Singleton', 'url' => $singleton->getName()];
        }

        foreach ($container->getFunctionImports() as $import) {
            /** @var FunctionImportInterface $import */
            if ($import->isIncludedInServiceDocument()) {
                $entries[] = ['name' => $import->getName(), 'kind' => 'FunctionImport', 'url' => $import->getName()];
            }
        }

        $response = new ODataResponse(null, 200, [
            'Content-Type' => 'application/json;odata.metadata=minimal;charset=utf-8',
            'OData-Version' => ODataVersion::current(),
        ]);

        $response->setCallback(static function () use ($context, $entries): void {
            echo json_encode([
                '@odata.context' => $context,
                'value'          => $entries,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        });

        return $response;
    }
}
