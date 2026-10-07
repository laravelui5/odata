<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

use Illuminate\Database\Eloquent\Model;
use LaravelUi5\OData\Edm\Type\TypeFacets;

/**
 * The facet forward-exit of model discovery.
 *
 * Discovery derives a column's facets from the table schema (`Nullable`, `Precision`/`Scale`,
 * `MaxLength`). Some facets are not a fact of the column but of the installation: a unit price
 * stored as `decimal(19,6)` is announced with the installation's price decimals. A host or
 * package that knows such a fact binds its own resolver; the default
 * ({@see \LaravelUi5\OData\Service\Discovery\ColumnFacetsAsDeclared}) returns the facets unchanged.
 *
 * Order per column: the schema's facets → this resolver → `#[ODataProperty(precision:, scale:,
 * nullable:)]`. The attribute is local to one model and wins.
 *
 * The resolver runs when the schema is **built** — on every request for a cold service, once in
 * `odata:cache` for a cached one. A cached service keeps the value it saw then; when the fact
 * changes, the cache must be rebuilt.
 *
 * One binding, not a chain: rebinding replaces the previous resolver. A package that wants to
 * keep another resolver's answer decorates it.
 */
interface ColumnFacetResolverInterface
{
    /**
     * @param class-string<Model> $modelClass the discovered model
     * @param string              $column     the column name as in the table
     * @param string|null         $cast       the model's cast for the column, as declared
     * @param TypeFacets          $facets     the facets derived from the column schema
     */
    public function resolve(string $modelClass, string $column, ?string $cast, TypeFacets $facets): TypeFacets;
}
