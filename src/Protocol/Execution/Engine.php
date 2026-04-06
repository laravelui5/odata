<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

use LaravelUi5\OData\Http\ODataResponse;
use LaravelUi5\OData\Exception\BadRequestException;
use LaravelUi5\OData\Protocol\Planning\EntityQueryPlan;
use LaravelUi5\OData\Protocol\Planning\EntitySetQueryPlan;
use LaravelUi5\OData\Protocol\Planning\FunctionInvocationPlan;
use LaravelUi5\OData\Protocol\Planning\MetadataQueryPlan;
use LaravelUi5\OData\Protocol\Planning\PropertyValuePlan;
use LaravelUi5\OData\Protocol\Planning\SingletonQueryPlan;
use LaravelUi5\OData\Protocol\Planning\QueryPlan;
use LaravelUi5\OData\Protocol\Planning\ServiceDocumentQueryPlan;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;

/**
 * OData execution engine.
 *
 * Receives a fully-resolved QueryPlan from QueryPlanner and dispatches to a
 * typed handler. Each handler returns an ODataResponse (StreamedResponse).
 */
final readonly class Engine
{
    public function __construct(
        private RuntimeSchemaInterface $schema,
        private string $serviceRoot,
    ) {}

    public function execute(QueryPlan $plan): ODataResponse
    {
        return match (true) {
            $plan instanceof MetadataQueryPlan        => (new MetadataHandler)->handle($plan),
            $plan instanceof ServiceDocumentQueryPlan => (new ServiceDocumentHandler($this->serviceRoot))->handle($plan),
            $plan instanceof EntitySetQueryPlan       => (new EntitySetHandler($this->schema, $this->serviceRoot))->handle($plan),
            $plan instanceof EntityQueryPlan          => (new EntityHandler($this->schema, $this->serviceRoot))->handle($plan),
            $plan instanceof FunctionInvocationPlan   => (new FunctionInvocationHandler($this->schema, $this->serviceRoot))->handle($plan),
            $plan instanceof SingletonQueryPlan       => (new SingletonHandler($this->schema, $this->serviceRoot))->handle($plan->singleton, $plan->select),
            $plan instanceof PropertyValuePlan       => (new PropertyValueHandler($this->schema, $this->serviceRoot))->handle($plan),

            default => throw new BadRequestException(
                'unsupported_plan',
                sprintf('No handler for plan type %s', $plan::class)
            ),
        };
    }
}
