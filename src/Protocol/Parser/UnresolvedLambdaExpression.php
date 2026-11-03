<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Parser;

use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpressionKind;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaOperator;

/**
 * An `any()`/`all()` as the parser read it: the navigation by name, the predicate unresolved.
 *
 * Exists only between {@see FilterParser::parse()} and {@see PropertyResolver::resolve()}, which replaces it with a
 * {@see \LaravelUi5\OData\Protocol\Planning\Expression\LambdaExpression} whose collection is the navigation property,
 * or answers 400. A tree that still holds one cannot be visited: `accept()` throws.
 *
 * @internal
 */
final readonly class UnresolvedLambdaExpression extends FilterExpression
{
    public function __construct(
        public string $navigation,
        public string $variable,
        public FilterExpression $predicate,
        public LambdaOperator $operator,
    ) {}

    public function kind(): FilterExpressionKind
    {
        return FilterExpressionKind::Unresolved;
    }
}
