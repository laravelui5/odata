<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning\Expression;

abstract readonly class FilterExpression
{
    abstract public function kind(): FilterExpressionKind;

    /**
     * Dispatch to the visitor method for this node's class. Drivers that do not implement all visitor methods
     * receive a PHP fatal error at call time — the same completeness guarantee Olingo achieves via Java interfaces.
     */
    final public function accept(FilterExpressionVisitor $visitor): mixed
    {
        return match (true) {
            $this instanceof LiteralExpression        => $visitor->visitLiteral($this),
            $this instanceof NullLiteralExpression    => $visitor->visitNullLiteral($this),
            $this instanceof PropertyPathExpression   => $visitor->visitPropertyPath($this),
            $this instanceof BinaryExpression         => $visitor->visitBinary($this),
            $this instanceof UnaryExpression          => $visitor->visitUnary($this),
            $this instanceof FunctionCallExpression   => $visitor->visitFunctionCall($this),
            $this instanceof LambdaExpression         => $visitor->visitLambda($this),
            $this instanceof LambdaVariableExpression => $visitor->visitLambdaVariable($this),
            default => throw new \LogicException(sprintf('No visitor method for %s.', static::class)),
        };
    }
}
