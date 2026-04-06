<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning\Expression;

use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;

final readonly class PropertyPathExpression extends FilterExpression
{
    /**
     * Ordered list of resolved model objects forming the path.
     *
     * Single structural property: [$property].
     * Navigation path: [$navProperty, $property].
     *
     * @param list<PropertyInterface|NavigationPropertyInterface> $segments
     */
    public function __construct(public readonly array $segments) {}

    public function kind(): FilterExpressionKind
    {
        return FilterExpressionKind::PropertyPath;
    }
}
