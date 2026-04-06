<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

/**
 * Self-describing entity set resolver.
 *
 * Implementations colocate the entity type definition with the query logic,
 * keeping everything in one class. Use this for entity sets that don't map
 * to a single Eloquent model or SQL table — e.g., aggregated views,
 * cross-model projections, or external API-backed data.
 *
 * Register via ODataService::discoverCustomEntitySet():
 *
 *   $this->discoverCustomEntitySet(BillableProjectsResolver::class);
 *
 * This single call adds the entity type and set to the Edm, and registers
 * the CustomBinding in the resolver map — no manual configure() or
 * registerBindings() wiring needed.
 */
interface CustomEntitySetInterface extends EntitySetResolverInterface
{
    /**
     * The entity set name as it appears in the OData service document.
     */
    public function entitySetName(): string;

    /**
     * The entity type definition for this entity set.
     *
     * @param string $namespace The service namespace (e.g. 'io.pragmatiqu')
     */
    public function entityType(string $namespace): EntityTypeInterface;
}
