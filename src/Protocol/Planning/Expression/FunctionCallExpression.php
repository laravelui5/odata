<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning\Expression;

final readonly class FunctionCallExpression extends FilterExpression
{
    /**
     * @param string                 $name      OData function name, e.g. 'tolower', 'contains'.
     * @param list<FilterExpression> $arguments
     */
    public function __construct(
        public readonly string $name,
        public readonly array  $arguments,
    ) {}

    public function kind(): FilterExpressionKind
    {
        return FilterExpressionKind::FunctionCall;
    }
}
