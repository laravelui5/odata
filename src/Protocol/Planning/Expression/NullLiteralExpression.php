<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning\Expression;

final readonly class NullLiteralExpression extends FilterExpression
{
    public function kind(): FilterExpressionKind
    {
        return FilterExpressionKind::NullLiteral;
    }
}
