<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Discovery;

use LaravelUi5\OData\Service\Contracts\ColumnAnnotationResolverInterface;

/**
 * The default annotation resolver: a column carries only what its model's `#[ODataAnnotate]` says.
 */
final class ColumnsUnannotated implements ColumnAnnotationResolverInterface
{
    public function annotations(string $modelClass, string $column): array
    {
        return [];
    }
}
