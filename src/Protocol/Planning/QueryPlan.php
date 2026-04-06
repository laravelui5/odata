<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

use LaravelUi5\OData\Service\Contracts\QueryPlanInterface;

/**
 * Sealed abstract base for all query plan types.
 * Subclasses are marker-only; all state lives in the concrete class constructors.
 */
abstract readonly class QueryPlan implements QueryPlanInterface {}
