<?php

declare(strict_types=1);

use LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\FunctionCallExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;

use function LaravelUi5\OData\Tests\Protocol\Parser\translateFilter;

use function LaravelUi5\OData\Tests\narrow;

// $compute expressions use the same filter parser for the expression part.
// We test the expression portion via translateFilter() since the "as alias"
// splitting is handled by QueryPlanner::parseCompute().

it('translates simple property expression', function () {
    $expr = translateFilter('origin');
    $expr = narrow(PropertyPathExpression::class, $expr);
});

it('translates concat function expression', function () {
    $expr = translateFilter("concat(origin, 'world')");
    $expr = narrow(FunctionCallExpression::class, $expr);
    expect($expr->name)->toBe('concat');
});

it('translates arithmetic expression', function () {
    $expr = translateFilter('id add 2');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Add);
});

it('translates boolean literal', function () {
    $expr = translateFilter('false');
    $expr = narrow(LiteralExpression::class, $expr);
    expect($expr->value)->toBe(false);
});
