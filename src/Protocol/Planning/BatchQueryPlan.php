<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

final readonly class BatchQueryPlan extends QueryPlan
{
    /** @param list<QueryPlan> $parts */
    public function __construct(
        public array $parts,
        public bool  $atomicity,
    ) {}
}
