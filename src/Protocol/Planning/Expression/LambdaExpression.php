<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning\Expression;

final readonly class LambdaExpression extends FilterExpression
{
    /**
     * @param PropertyPathExpression $collection  The navigation property being iterated.
     * @param string                 $variable    The lambda iteration variable, e.g. 'x' in 'tags/any(x: ...)'.
     * @param FilterExpression       $predicate   The predicate applied to each element. The visitor recurses into it.
     * @param LambdaOperator         $operator
     */
    public function __construct(
        public readonly PropertyPathExpression $collection,
        public readonly string                 $variable,
        public readonly FilterExpression       $predicate,
        public readonly LambdaOperator         $operator,
    ) {}

    public function kind(): FilterExpressionKind
    {
        return FilterExpressionKind::Lambda;
    }
}
