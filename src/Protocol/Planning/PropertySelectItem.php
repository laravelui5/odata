<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;

final readonly class PropertySelectItem extends SelectItem
{
    public function __construct(public readonly PropertyInterface $property) {}
}
