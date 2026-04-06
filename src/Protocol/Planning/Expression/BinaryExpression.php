<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning\Expression;

final readonly class BinaryExpression extends FilterExpression
{
    public function __construct(
        public readonly FilterExpression $left,
        public readonly BinaryOperator   $operator,
        public readonly FilterExpression $right,
    ) {}

    public function kind(): FilterExpressionKind
    {
        return FilterExpressionKind::Binary;
    }
}
