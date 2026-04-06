<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

/**
 * Marker interface for all query plans.
 *
 * Concrete plan classes live in Protocol\Planning\ (an outer ring that
 * Service\ must not import). This marker exists solely so that the resolver
 * interfaces in Service\Contracts\ can carry a type-checked parameter without
 * creating an inward dependency.
 *
 * Callers in Protocol\ receive the resolver from RuntimeSchemaInterface and
 * safely pass the concrete plan — the runtime type is always correct because
 * SchemaBuilder only pairs entity-set resolvers with entity-set plans.
 *
 * @see EntitySetResolverInterface
 * @see FunctionResolverInterface
 */
interface QueryPlanInterface
{
}
