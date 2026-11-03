<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Parser;

use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpressionKind;

/**
 * A property path as the parser read it: names, not yet properties.
 *
 * Exists only between {@see FilterParser::parse()} and {@see PropertyResolver::resolve()}, which replaces it with a
 * {@see \LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression} or answers 400. A tree that still holds
 * one cannot be visited: `accept()` throws.
 *
 * @internal
 */
final readonly class UnresolvedPathExpression extends FilterExpression
{
    /**
     * @param list<string> $names the segments as written, e.g. ['origin']
     */
    public function __construct(public array $names) {}

    public function kind(): FilterExpressionKind
    {
        return FilterExpressionKind::Unresolved;
    }
}
