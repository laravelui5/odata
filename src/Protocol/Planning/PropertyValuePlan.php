<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;

/**
 * Plan for accessing a single property value: /EntitySet(key)/property
 *
 * Optionally suffixed with /$value for raw value access.
 */
final readonly class PropertyValuePlan extends QueryPlan
{
    public function __construct(
        public EntitySetInterface $target,
        public KeyExpression      $key,
        public PropertyInterface  $property,
        public bool               $rawValue,  // true when /$value suffix is present
    ) {}
}
