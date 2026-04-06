<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning\Expression;

abstract readonly class FilterExpression
{
    abstract public function kind(): FilterExpressionKind;

    /**
     * Dispatch to the correct visitor method via match on kind().
     * Drivers that do not implement all visitor methods receive a PHP fatal error
     * at call time — the same completeness guarantee Olingo achieves via Java interfaces.
     */
    final public function accept(FilterExpressionVisitor $visitor): mixed
    {
        return match ($this->kind()) {
            FilterExpressionKind::Literal        => $visitor->visitLiteral($this),        // @phpstan-ignore-line
            FilterExpressionKind::NullLiteral    => $visitor->visitNullLiteral($this),    // @phpstan-ignore-line
            FilterExpressionKind::PropertyPath   => $visitor->visitPropertyPath($this),   // @phpstan-ignore-line
            FilterExpressionKind::Binary         => $visitor->visitBinary($this),         // @phpstan-ignore-line
            FilterExpressionKind::Unary          => $visitor->visitUnary($this),          // @phpstan-ignore-line
            FilterExpressionKind::FunctionCall   => $visitor->visitFunctionCall($this),   // @phpstan-ignore-line
            FilterExpressionKind::Lambda         => $visitor->visitLambda($this),         // @phpstan-ignore-line
            FilterExpressionKind::LambdaVariable => $visitor->visitLambdaVariable($this), // @phpstan-ignore-line
        };
    }
}
