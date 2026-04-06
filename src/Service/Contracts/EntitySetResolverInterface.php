<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

/**
 * Resolves an entity-set query plan into a lazy stream of entities.
 *
 * Implementations live in Driver\ and translate the plan into database queries.
 * The plan parameter is typed as QueryPlanInterface to keep Service\ free of
 * Protocol\ imports; at runtime the value is always an EntitySetQueryPlan.
 *
 * @see QueryPlanInterface
 */
interface EntitySetResolverInterface
{
    /**
     * Execute the plan and yield entity data one record at a time.
     *
     * The generator yields raw associative arrays or value objects — the exact
     * shape is defined by the driver. Protocol\Serialization\ consumes the
     * generator and writes directly to the output stream.
     *
     * @param QueryPlanInterface $plan  At runtime always Protocol\Planning\EntitySetQueryPlan
     * @return \Generator<mixed>
     */
    public function resolve(QueryPlanInterface $plan): \Generator;

    /**
     * Return the total number of entities matching the plan's filter,
     * ignoring $top/$skip pagination.
     *
     * Called by the engine when $count=true is present on the request.
     *
     * @param QueryPlanInterface $plan  At runtime always Protocol\Planning\EntitySetQueryPlan
     */
    public function count(QueryPlanInterface $plan): int;
}
