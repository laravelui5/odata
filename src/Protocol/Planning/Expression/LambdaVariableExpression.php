<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning\Expression;

final readonly class LambdaVariableExpression extends FilterExpression
{
    public function __construct(public readonly string $variable) {}

    public function kind(): FilterExpressionKind
    {
        return FilterExpressionKind::LambdaVariable;
    }
}
