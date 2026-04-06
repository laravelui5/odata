<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

use LaravelUi5\OData\Edm\Contracts\EdmxInterface;

final readonly class MetadataQueryPlan extends QueryPlan
{
    public function __construct(public EdmxInterface $edmx) {}
}
