<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

/**
 * The number of `rollup` or `rolluprecursive` operators allowed in a `groupby` transformation
 */
enum RollupType: int
{
    case None = 0;
    case SingleHierarchy = 1;
    case MultipleHierarchies = 2;
}
