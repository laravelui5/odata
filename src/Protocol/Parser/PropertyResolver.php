<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Parser;

use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Exception\BadRequestException;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\FunctionCallExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\NullLiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\UnaryExpression;

/**
 * Resolves unresolved string property names in a FilterExpression tree
 * against an EntityTypeInterface, and applies type hints from properties
 * to adjacent literals.
 */
final readonly class PropertyResolver
{
    /**
     * Resolve all property names and apply type hints in the given expression.
     */
    public function resolve(FilterExpression $expr, EntityTypeInterface $entityType): FilterExpression
    {
        return $this->walk($expr, $entityType, null);
    }

    private function walk(FilterExpression $expr, EntityTypeInterface $entityType, ?string $hintEdmType): FilterExpression
    {
        if ($expr instanceof PropertyPathExpression) {
            return $this->resolveProperty($expr, $entityType);
        }

        if ($expr instanceof LiteralExpression && $hintEdmType !== null) {
            return new LiteralExpression($expr->value, $hintEdmType);
        }

        if ($expr instanceof NullLiteralExpression) {
            return $expr;
        }

        if ($expr instanceof LiteralExpression) {
            return $expr;
        }

        if ($expr instanceof BinaryExpression) {
            return $this->resolveBinary($expr, $entityType);
        }

        if ($expr instanceof UnaryExpression) {
            return new UnaryExpression(
                $expr->operator,
                $this->walk($expr->operand, $entityType, null),
            );
        }

        if ($expr instanceof FunctionCallExpression) {
            $resolvedArgs = array_map(
                fn(FilterExpression $arg) => $this->walk($arg, $entityType, null),
                $expr->arguments,
            );
            return new FunctionCallExpression($expr->name, $resolvedArgs);
        }

        if ($expr instanceof LambdaExpression) {
            return $this->resolveLambda($expr, $entityType);
        }

        return $expr;
    }

    private function resolveBinary(BinaryExpression $expr, EntityTypeInterface $entityType): BinaryExpression
    {
        $left = $this->walk($expr->left, $entityType, null);

        // Infer type hint from left-side property for comparison operators
        $hintType = null;
        if ($this->isComparisonOperator($expr->operator) && $left instanceof PropertyPathExpression) {
            $last = $left->segments[count($left->segments) - 1] ?? null;
            if ($last instanceof PropertyInterface) {
                $hintType = $last->getType()->getQualifiedName();
            }
        }

        $right = $this->walk($expr->right, $entityType, $hintType);

        return new BinaryExpression($left, $expr->operator, $right);
    }

    private function isComparisonOperator(BinaryOperator $op): bool
    {
        return match ($op) {
            BinaryOperator::Eq, BinaryOperator::Ne,
            BinaryOperator::Gt, BinaryOperator::Ge,
            BinaryOperator::Lt, BinaryOperator::Le,
            BinaryOperator::Has, BinaryOperator::In => true,
            default => false,
        };
    }

    private function resolveLambda(LambdaExpression $expr, EntityTypeInterface $entityType): LambdaExpression
    {
        // Resolve the navigation property on the collection path
        $navPropName = $expr->collection->segments[0] ?? null;
        if (is_string($navPropName)) {
            $navProperty = $entityType->getNavigationProperty($navPropName);
            if ($navProperty === null) {
                throw new BadRequestException(
                    'unknown_navigation_property',
                    "Unknown navigation property in lambda: {$navPropName}"
                );
            }

            $collection = new PropertyPathExpression([$navProperty]);
            $predicate = $this->walk($expr->predicate, $navProperty->getTargetType(), null);

            return new LambdaExpression($collection, $expr->variable, $predicate, $expr->operator);
        }

        return $expr;
    }

    private function resolveProperty(PropertyPathExpression $expr, EntityTypeInterface $entityType): PropertyPathExpression
    {
        $segments = [];

        foreach ($expr->segments as $segment) {
            if (is_string($segment)) {
                $property = $entityType->getProperty($segment);
                if ($property === null) {
                    throw new BadRequestException(
                        'unknown_property',
                        "Unknown property in \$filter: {$segment}"
                    );
                }
                $segments[] = $property;
            } else {
                $segments[] = $segment;
            }
        }

        return new PropertyPathExpression($segments);
    }
}
