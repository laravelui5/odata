<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;

final readonly class SingletonQueryPlan extends QueryPlan
{
    public function __construct(
        public SingletonInterface $singleton,
        public SelectList         $select,
    ) {}
}
