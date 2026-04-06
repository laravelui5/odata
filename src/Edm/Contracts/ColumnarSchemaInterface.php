<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts;

use LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeEnum;

/**
 * Declares a typed column schema: column names with their primitive types
 * and the key columns that uniquely identify a row.
 *
 * This is the common denominator between OData entity sets (where parameters
 * arrive via $filter) and Core artifact types like Report, AnalyticsSet, and
 * ValueHelp (where parameters arrive via artifact-specific mechanisms).
 *
 * The interface captures only the schema — "I produce typed rows with this
 * shape" — without prescribing how the data is queried or how parameters
 * are supplied. That responsibility belongs to the consuming layer:
 *
 * - In odata: {@see \LaravelUi5\OData\Service\AbstractEntitySet} implements
 *   this interface and inherits SQL query execution from SqlEntitySetResolver.
 * - In Core: SqlQueryInterface extends this interface and adds the query
 *   source contract for Report/AnalyticsSet/ValueHelp artifacts.
 *
 * @see PrimitiveTypeEnum for the available column types
 */
interface ColumnarSchemaInterface
{
    /**
     * Flat column definitions using PrimitiveTypeEnum directly.
     *
     * @return array<string, PrimitiveTypeEnum>
     */
    public function columns(): array;

    /**
     * Primary key column name(s).
     *
     * @return list<string>
     */
    public function key(): array;
}
