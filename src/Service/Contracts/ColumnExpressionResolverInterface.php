<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

/**
 * The expression forward-exit of query execution.
 *
 * `$filter`, `$orderby` and `$search` name a property; the engine compares, sorts and searches by the column of that
 * name. Some properties are not a column but an expression on the query — a translated text a package joins in, say.
 * A host or package that knows such a property binds its own resolver; the default
 * ({@see \LaravelUi5\OData\Driver\Sql\ColumnsAsNamed}) answers null, so every property stays its column.
 *
 * `$source` is the Eloquent model class of an Eloquent-backed set, or the class of a custom entity set. The answer is
 * raw SQL and is used as is — in `WHERE`, `ORDER BY` and the `LIKE` of `$search`. **The resolver guarantees that it is
 * valid on every query of that source**, whatever the engine selects: a join it relies on must be part of every such
 * query (for a model: its global scope). The engine reads the property's value from the row as before; the
 * expression changes what is compared, not what is returned.
 *
 * Only a property of the set itself takes an expression; a path through a navigation property keeps its column.
 * `$apply` is not covered.
 *
 * One binding, not a chain: rebinding replaces the previous resolver. A package that wants to keep another
 * resolver's answer decorates it.
 */
interface ColumnExpressionResolverInterface
{
    /**
     * @param string $source   the model class (Eloquent) or the entity-set class (custom set)
     * @param string $property the property name as the request names it
     *
     * @return string|null raw SQL to use instead of the column, or null for the column itself
     */
    public function expression(string $source, string $property): ?string;
}
