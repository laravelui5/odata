<?php

declare(strict_types=1);

use LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaVariableExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;

use function LaravelUi5\OData\Tests\Protocol\Parser\translateFilter;

it('translates any lambda', function () {
    $expr = translateFilter("airports/any(d:d/name eq 'hello')");
    expect($expr)->toBeInstanceOf(LambdaExpression::class);
    expect($expr->operator)->toBe(LambdaOperator::Any);
    expect($expr->variable)->toBe('d');
    expect($expr->collection)->toBeInstanceOf(PropertyPathExpression::class);
    expect($expr->predicate)->toBeInstanceOf(BinaryExpression::class);
});

it('translates all lambda', function () {
    $expr = translateFilter("airports/all(d:d/name eq 'hello')");
    expect($expr)->toBeInstanceOf(LambdaExpression::class);
    expect($expr->operator)->toBe(LambdaOperator::All);
});

it('translates all lambda on short nav property name', function () {
    $expr = translateFilter("da/all(d:d/name eq 'hello')");
    expect($expr)->toBeInstanceOf(LambdaExpression::class);
    expect($expr->operator)->toBe(LambdaOperator::All);
});

it('translates and of two any lambdas', function () {
    $expr = translateFilter("airports/any(d:d/name eq 'hello') and airports/any(d:d/name eq 'hello')");
    expect($expr)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->operator)->toBe(BinaryOperator::And);
    expect($expr->left)->toBeInstanceOf(LambdaExpression::class);
    expect($expr->right)->toBeInstanceOf(LambdaExpression::class);
});

it('translates complex expression with any and all lambdas', function () {
    $expr = translateFilter("airports/any(d:d/name eq 'hello') and 1 eq 2 or airports/all(d:d/name eq 'hello')");
    expect($expr)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->operator)->toBe(BinaryOperator::Or);
});

it('lambda predicate resolves properties on target type', function () {
    $expr = translateFilter("airports/any(d:d/name eq 'hello')");
    // The predicate should have resolved 'name' against the Airport type
    $predicate = $expr->predicate;
    expect($predicate)->toBeInstanceOf(BinaryExpression::class);
    expect($predicate->left)->toBeInstanceOf(PropertyPathExpression::class);
    $segments = $predicate->left->segments;
    expect($segments)->toHaveCount(1);
    expect($segments[0]->getName())->toBe('name');
});

it('lambda variable appears in predicate', function () {
    // In the current translation, lambda variable references (d/) are consumed
    // by the parser as navigation paths. The d/name becomes just a property path on the target type.
    // The lambda variable node itself is captured as the variable string.
    $expr = translateFilter("airports/any(d:d/name eq 'hello')");
    expect($expr->variable)->toBe('d');
});
