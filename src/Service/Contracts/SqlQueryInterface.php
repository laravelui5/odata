<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

use LaravelUi5\OData\Edm\Contracts\ColumnarSchemaInterface;

/**
 * A SQL-backed data source with typed column schema.
 *
 * Combines the pure schema contract ({@see ColumnarSchemaInterface}) with the
 * query source contract ({@see EntitySetSourceInterface}) into a single
 * interface for SQL-derived data sources that describe their own shape.
 *
 * Primary consumers:
 * - {@see \LaravelUi5\OData\Service\AbstractEntitySet} — OData custom entity sets
 * - Core artifact types (Report, AnalyticsSet, ValueHelp) via their own extensions
 */
interface SqlQueryInterface extends ColumnarSchemaInterface, EntitySetSourceInterface {}
