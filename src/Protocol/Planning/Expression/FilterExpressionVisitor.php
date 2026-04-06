<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning\Expression;

/**
 * Visitor interface for FilterExpression trees.
 *
 * Methods receive the raw node. The visitor is responsible for recursing into
 * children — this gives full control over traversal order, short-circuiting,
 * and context threading (e.g. lambda variable scope injection).
 *
 * Drivers implement this interface to translate a FilterExpression tree into
 * a storage-layer query (Eloquent WHERE clause, SQL fragment, etc.).
 */
interface FilterExpressionVisitor
{
    public function visitLiteral(LiteralExpression $node): mixed;
    public function visitNullLiteral(NullLiteralExpression $node): mixed;
    public function visitPropertyPath(PropertyPathExpression $node): mixed;
    public function visitBinary(BinaryExpression $node): mixed;
    public function visitUnary(UnaryExpression $node): mixed;
    public function visitFunctionCall(FunctionCallExpression $node): mixed;
    public function visitLambda(LambdaExpression $node): mixed;
    public function visitLambdaVariable(LambdaVariableExpression $node): mixed;
}
