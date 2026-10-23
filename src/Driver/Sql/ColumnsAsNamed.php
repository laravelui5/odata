<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Driver\Sql;

use LaravelUi5\OData\Service\Contracts\ColumnExpressionResolverInterface;

/** The default: every property is the column of its name. */
final class ColumnsAsNamed implements ColumnExpressionResolverInterface
{
    public function expression(string $source, string $property): ?string
    {
        return null;
    }
}
