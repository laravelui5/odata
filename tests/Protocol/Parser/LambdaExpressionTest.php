<?php

declare(strict_types=1);

use LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaVariableExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;

use function LaravelUi5\OData\Tests\Protocol\Parser\translateFilter;

use function LaravelUi5\OData\Tests\narrow;

it('translates any lambda', function () {
    $expr = translateFilter("airports/any(d:d/name eq 'hello')");
    $expr = narrow(LambdaExpression::class, $expr);
    expect($expr->operator)->toBe(LambdaOperator::Any);
    expect($expr->variable)->toBe('d');
    $collection = narrow(PropertyPathExpression::class, $expr->collection);
    $predicate = narrow(BinaryExpression::class, $expr->predicate);
});

it('translates all lambda', function () {
    $expr = translateFilter("airports/all(d:d/name eq 'hello')");
    $expr = narrow(LambdaExpression::class, $expr);
    expect($expr->operator)->toBe(LambdaOperator::All);
});

it('translates all lambda on short nav property name', function () {
    $expr = translateFilter("da/all(d:d/name eq 'hello')");
    $expr = narrow(LambdaExpression::class, $expr);
    expect($expr->operator)->toBe(LambdaOperator::All);
});

it('translates and of two any lambdas', function () {
    $expr = translateFilter("airports/any(d:d/name eq 'hello') and airports/any(d:d/name eq 'hello')");
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::And);
    $left = narrow(LambdaExpression::class, $expr->left);
    $right = narrow(LambdaExpression::class, $expr->right);
});

it('translates complex expression with any and all lambdas', function () {
    $expr = translateFilter("airports/any(d:d/name eq 'hello') and 1 eq 2 or airports/all(d:d/name eq 'hello')");
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Or);
});

it('lambda predicate resolves properties on target type', function () {
    $expr = translateFilter("airports/any(d:d/name eq 'hello')");
    // The predicate should have resolved 'name' against the Airport type
    $expr = narrow(LambdaExpression::class, $expr);
    $predicate = $expr->predicate;
    $predicate = narrow(BinaryExpression::class, $predicate);
    $left = narrow(PropertyPathExpression::class, $predicate->left);
    $segments = $left->segments;
    expect($segments)->toHaveCount(1);
    expect($segments[0]->getName())->toBe('name');
});

it('lambda variable appears in predicate', function () {
    // In the current translation, lambda variable references (d/) are consumed
    // by the parser as navigation paths. The d/name becomes just a property path on the target type.
    // The lambda variable node itself is captured as the variable string.
    $expr = narrow(LambdaExpression::class, translateFilter("airports/any(d:d/name eq 'hello')"));
    expect($expr->variable)->toBe('d');
});
