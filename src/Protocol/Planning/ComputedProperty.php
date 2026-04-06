<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

/**
 * A computed property definition from $compute.
 *
 * Carries the raw OData expression string and the alias name.
 * The expression is evaluated at response time against each row.
 */
final readonly class ComputedProperty
{
    public function __construct(
        public string $alias,
        public string $expression,
    ) {}
}
