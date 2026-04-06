<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

use LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression;

final readonly class KeyExpression
{
    /**
     * @param array<string, LiteralExpression> $values  Property name → literal.
     *        Single-key entity: ['id' => new LiteralExpression(42, 'Edm.Int32')]
     */
    public function __construct(public readonly array $values) {}

    public function isSingleKey(): bool
    {
        return count($this->values) === 1;
    }
}
