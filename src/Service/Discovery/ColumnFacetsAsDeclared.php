<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Discovery;

use LaravelUi5\OData\Edm\Type\TypeFacets;
use LaravelUi5\OData\Service\Contracts\ColumnFacetResolverInterface;

/**
 * The default facet resolver: the column schema's facets stand as they are.
 */
final class ColumnFacetsAsDeclared implements ColumnFacetResolverInterface
{
    public function resolve(string $modelClass, string $column, ?string $cast, TypeFacets $facets): TypeFacets
    {
        return $facets;
    }
}
