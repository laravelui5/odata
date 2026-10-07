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
 * Its consumer in this package is {@see \LaravelUi5\OData\Service\AbstractEntitySet}, which is
 * what makes a custom entity set self-describing: `columns()` and `key()` become the entity type,
 * `query()` the rows.
 *
 * Other layers that describe SQL-backed sources the same way — a reporting or analytics layer —
 * may adopt it; none does today.
 */
interface SqlQueryInterface extends ColumnarSchemaInterface, EntitySetSourceInterface {}
