<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning\Expression;

final readonly class UnaryExpression extends FilterExpression
{
    public function __construct(
        public readonly UnaryOperator    $operator,
        public readonly FilterExpression $operand,
    ) {}

    public function kind(): FilterExpressionKind
    {
        return FilterExpressionKind::Unary;
    }
}
