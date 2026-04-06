<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning\Expression;

final readonly class LiteralExpression extends FilterExpression
{
    /**
     * @param mixed  $value   Typed PHP scalar (int, float, string, bool, or a value object for dates/guids).
     * @param string $edmType Fully qualified Edm type, e.g. 'Edm.String', 'Edm.Int32', 'Edm.DateTimeOffset'.
     */
    public function __construct(
        public readonly mixed  $value,
        public readonly string $edmType,
    ) {}

    public function kind(): FilterExpressionKind
    {
        return FilterExpressionKind::Literal;
    }
}
