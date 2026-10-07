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
            FilterExpressionKind::Literal        => $visitor->visitLiteral($this),
            FilterExpressionKind::NullLiteral    => $visitor->visitNullLiteral($this),
            FilterExpressionKind::PropertyPath   => $visitor->visitPropertyPath($this),
            FilterExpressionKind::Binary         => $visitor->visitBinary($this),
            FilterExpressionKind::Unary          => $visitor->visitUnary($this),
            FilterExpressionKind::FunctionCall   => $visitor->visitFunctionCall($this),
            FilterExpressionKind::Lambda         => $visitor->visitLambda($this),
            FilterExpressionKind::LambdaVariable => $visitor->visitLambdaVariable($this),
        };
    }
}
