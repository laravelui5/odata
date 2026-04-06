<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

/**
 * Resolves a single-entity plan into one row, or null when not found.
 *
 * Intended to be implemented alongside EntitySetResolverInterface by the
 * same driver class (e.g. EloquentEntitySetResolver). EntityHandler retrieves
 * the resolver via RuntimeSchemaInterface::getResolver() and then checks
 * instanceof EntityResolverInterface before calling resolveOne().
 *
 * The plan parameter is typed as QueryPlanInterface to keep Service\ free of
 * Protocol\ imports; at runtime the value is always Protocol\Planning\EntityQueryPlan.
 *
 * @see EntitySetResolverInterface
 */
interface EntityResolverInterface
{
    /**
     * Execute the plan and return the matching entity as an associative array,
     * or null when no entity with the given key exists.
     *
     * @param QueryPlanInterface $plan  At runtime always Protocol\Planning\EntityQueryPlan
     * @return array<string, mixed>|null
     */
    public function resolveOne(QueryPlanInterface $plan): ?array;
}
