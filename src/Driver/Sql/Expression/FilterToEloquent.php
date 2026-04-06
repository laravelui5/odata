<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Driver\Sql\Expression;

use Illuminate\Database\Eloquent\Builder;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpressionVisitor;
use LaravelUi5\OData\Protocol\Planning\Expression\FunctionCallExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaVariableExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\NullLiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\UnaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\UnaryOperator;

/**
 * Translates a FilterExpression tree into Eloquent Builder WHERE clauses.
 *
 * Visitor methods receive the raw node and recurse into children themselves,
 * following the .NET QueryBinder model (not Olingo's post-order accept).
 */
final class FilterToEloquent implements FilterExpressionVisitor
{
    public function __construct(private readonly Builder $builder) {}

    public function apply(FilterExpression $expression): void
    {
        $expression->accept($this);
    }

    public function visitLiteral(LiteralExpression $node): mixed
    {
        return $node->value;
    }

    public function visitNullLiteral(NullLiteralExpression $node): mixed
    {
        return null;
    }

    public function visitPropertyPath(PropertyPathExpression $node): mixed
    {
        // Return the column name of the last segment in the path.
        $last = $node->segments[count($node->segments) - 1];
        return $last->getName();
    }

    public function visitBinary(BinaryExpression $node): mixed
    {
        match ($node->operator) {
            BinaryOperator::And => $this->applyAnd($node),
            BinaryOperator::Or  => $this->applyOr($node),
            BinaryOperator::Eq  => $this->applyComparison($node, '='),
            BinaryOperator::Ne  => $this->applyComparison($node, '<>'),
            BinaryOperator::Gt  => $this->applyComparison($node, '>'),
            BinaryOperator::Ge  => $this->applyComparison($node, '>='),
            BinaryOperator::Lt  => $this->applyComparison($node, '<'),
            BinaryOperator::Le  => $this->applyComparison($node, '<='),
            BinaryOperator::In  => $this->applyIn($node),
            default             => null, // arithmetic/has operators not supported at WHERE level
        };

        return null;
    }

    public function visitUnary(UnaryExpression $node): mixed
    {
        if ($node->operator === UnaryOperator::Not) {
            $this->builder->whereNot(function (Builder $q) use ($node): void {
                $node->operand->accept(new self($q));
            });
        }

        return null;
    }

    public function visitFunctionCall(FunctionCallExpression $node): mixed
    {
        $args = $node->arguments;

        match (strtolower($node->name)) {
            'contains'   => $this->applyLike($args[0], $args[1], '%', '%'),
            'startswith' => $this->applyLike($args[0], $args[1], '', '%'),
            'endswith'   => $this->applyLike($args[0], $args[1], '%', ''),
            default      => null,
        };

        return null;
    }

    public function visitLambda(LambdaExpression $node): mixed
    {
        $navProperty = $node->collection->segments[0] ?? null;
        if ($navProperty === null) {
            return null;
        }

        $relationName = $navProperty->getName();

        if ($node->operator === LambdaOperator::Any) {
            $this->builder->whereHas($relationName, function (Builder $q) use ($node): void {
                $node->predicate->accept(new self($q));
            });
        } else {
            // all(): every related entity must match the predicate
            // ≡ no related entity fails the predicate
            $this->builder->whereDoesntHave($relationName, function (Builder $q) use ($node): void {
                $q->whereNot(function (Builder $inner) use ($node): void {
                    $node->predicate->accept(new self($inner));
                });
            });
        }

        return null;
    }

    public function visitLambdaVariable(LambdaVariableExpression $node): mixed
    {
        return null;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function applyAnd(BinaryExpression $node): void
    {
        $this->builder->where(function (Builder $q) use ($node): void {
            $node->left->accept(new self($q));
        });
        $this->builder->where(function (Builder $q) use ($node): void {
            $node->right->accept(new self($q));
        });
    }

    private function applyOr(BinaryExpression $node): void
    {
        $this->builder->where(function (Builder $q) use ($node): void {
            $node->left->accept(new self($q));
        })->orWhere(function (Builder $q) use ($node): void {
            $node->right->accept(new self($q));
        });
    }

    private function applyComparison(BinaryExpression $node, string $op): void
    {
        $column = $node->left->accept($this);
        $value  = $node->right->accept($this);

        if ($value === null) {
            match ($op) {
                '='  => $this->builder->whereNull($column),
                '<>' => $this->builder->whereNotNull($column),
                default => null,
            };
            return;
        }

        $this->builder->where($column, $op, $value);
    }

    private function applyIn(BinaryExpression $node): void
    {
        $column = $node->left->accept($this);
        $values = $node->right->accept($this);

        $this->builder->whereIn($column, (array) $values);
    }

    private function applyLike(FilterExpression $propExpr, FilterExpression $valExpr, string $prefix, string $suffix): void
    {
        $column = $this->unwrapCaseFunction($propExpr)->accept($this);
        $value  = $this->unwrapCaseFunction($valExpr)->accept($this);

        $this->builder->where($column, 'like', $prefix . $value . $suffix);
    }

    /**
     * Strip tolower()/toupper() wrappers — SQL LIKE is case-insensitive
     * on the default collations used by MySQL and SQLite.
     */
    private function unwrapCaseFunction(FilterExpression $expr): FilterExpression
    {
        if ($expr instanceof FunctionCallExpression
            && in_array(strtolower($expr->name), ['tolower', 'toupper'], true)
            && count($expr->arguments) === 1
        ) {
            return $expr->arguments[0];
        }

        return $expr;
    }
}
