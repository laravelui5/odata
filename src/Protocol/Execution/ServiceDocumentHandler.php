<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

use LaravelUi5\OData\Http\ODataResponse;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Protocol\Planning\ServiceDocumentQueryPlan;

/**
 * Handles ServiceDocumentQueryPlan — produces the OData service document JSON.
 *
 * Emits:
 *   {"@odata.context":"<serviceRoot>$metadata","value":[
 *     {"name":"Products","kind":"EntitySet","url":"Products"},
 *     ...
 *   ]}
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
            $entry = ['name' => $set->getName(), 'kind' => 'EntitySet', 'url' => $set->getName()];
            $entries[] = $entry;
        }

        foreach ($container->getSingletons() as $singleton) {
            /** @var SingletonInterface $singleton */
            $entry = ['name' => $singleton->getName(), 'kind' => 'Singleton', 'url' => $singleton->getName()];
            $entries[] = $entry;
        }

        $response = new ODataResponse(null, 200, [
            'Content-Type' => 'application/json;odata.metadata=minimal;charset=utf-8',
            'OData-Version' => '4.0',
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
