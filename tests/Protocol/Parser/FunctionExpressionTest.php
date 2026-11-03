<?php

declare(strict_types=1);

use LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\FunctionCallExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;

use function LaravelUi5\OData\Tests\Protocol\Parser\translateFilter;

use function LaravelUi5\OData\Tests\narrow;

// ── String functions ────────────────────────────────────────────────────

it('translates contains()', function () {
    $expr = translateFilter("contains(origin, 'b')");
    $expr = narrow(FunctionCallExpression::class, $expr);
    expect($expr->name)->toBe('contains');
    expect($expr->arguments)->toHaveCount(2);
    $item0 = narrow(PropertyPathExpression::class, $expr->arguments[0]);
    $item1 = narrow(LiteralExpression::class, $expr->arguments[1]);
});

it('translates endswith()', function () {
    $expr = translateFilter("endswith(origin, 'b')");
    $expr = narrow(FunctionCallExpression::class, $expr);
    expect($expr->name)->toBe('endswith');
});

it('translates startswith()', function () {
    $expr = translateFilter("startswith(origin,'a')");
    $expr = narrow(FunctionCallExpression::class, $expr);
    expect($expr->name)->toBe('startswith');
});

it('translates concat with two arguments', function () {
    $expr = translateFilter("concat(origin, 'abc') eq '123abc'");
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('concat');
    expect($left->arguments)->toHaveCount(2);
});

it('translates concat with three arguments', function () {
    $expr = translateFilter("concat(origin, 'abc', 4.0) eq '123abc'");
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->arguments)->toHaveCount(3);
});

it('translates concat with property arguments', function () {
    $expr = translateFilter("concat(origin, id) eq '123abc'");
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    $item0 = narrow(PropertyPathExpression::class, $left->arguments[0]);
    $item1 = narrow(PropertyPathExpression::class, $left->arguments[1]);
});

it('translates nested concat', function () {
    $expr = translateFilter("concat(origin, concat(id, 4)) eq '123abc'");
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    $item1 = narrow(FunctionCallExpression::class, $left->arguments[1]);
});

it('translates indexof()', function () {
    $expr = translateFilter("indexof(origin,'abc123') eq 1");
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('indexof');
});

it('translates length()', function () {
    $expr = translateFilter('length(origin) eq 1');
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('length');
    expect($left->arguments)->toHaveCount(1);
});

it('translates substring with start index', function () {
    $expr = translateFilter("substring(origin,1) eq 'abc123'");
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('substring');
    expect($left->arguments)->toHaveCount(2);
});

it('translates substring with start and length', function () {
    $expr = translateFilter("substring(origin,1,4) eq 'abc123'");
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->arguments)->toHaveCount(3);
});

it('translates matchesPattern()', function () {
    $expr = translateFilter("matchesPattern(origin,'^A.*e\$')");
    $expr = narrow(FunctionCallExpression::class, $expr);
    expect($expr->name)->toBe('matchesPattern');
});

it('translates tolower()', function () {
    $expr = translateFilter("tolower(origin) eq 'abc123'");
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('tolower');
});

it('translates toupper()', function () {
    $expr = translateFilter("toupper(origin) eq 'abc123'");
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('toupper');
});

it('translates trim()', function () {
    $expr = translateFilter("trim(origin) eq 'abc123'");
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('trim');
});

// ── Math functions ──────────────────────────────────────────────────────

it('translates ceiling()', function () {
    $expr = translateFilter('ceiling(origin) eq 4');
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('ceiling');
});

it('translates floor()', function () {
    $expr = translateFilter('floor(origin) eq 4');
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('floor');
});

it('translates round()', function () {
    $expr = translateFilter('round(origin) eq 4');
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('round');
});

// ── Date/time functions ─────────────────────────────────────────────────

it('translates date()', function () {
    $expr = translateFilter('date(origin) eq 2001-01-01');
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('date');
});

it('translates day()', function () {
    $expr = translateFilter('day(origin) eq 4');
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('day');
});

it('translates hour()', function () {
    $expr = translateFilter('hour(origin) eq 3');
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('hour');
});

it('translates minute()', function () {
    $expr = translateFilter('minute(origin) eq 33');
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('minute');
});

it('translates month()', function () {
    $expr = translateFilter('month(origin) eq 11');
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('month');
});

it('translates now()', function () {
    $expr = translateFilter('now() eq 10:00:00');
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('now');
    expect($left->arguments)->toHaveCount(0);
});

it('translates second()', function () {
    $expr = translateFilter('second(origin) eq 44');
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('second');
});

it('translates time()', function () {
    $expr = translateFilter('time(origin) eq 10:00:00');
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('time');
});

it('translates year()', function () {
    $expr = translateFilter('year(origin) eq 1999');
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('year');
});

// ── Cast function ───────────────────────────────────────────────────────

it('translates cast() function', function () {
    $expr = translateFilter("(contains(tolower(cast(origin, 'Edm.String')),'alpha'))");
    $expr = narrow(FunctionCallExpression::class, $expr);
    expect($expr->name)->toBe('contains');
    // First arg: tolower(cast(...))
    $item0 = narrow(FunctionCallExpression::class, $expr->arguments[0]);
    expect($item0->name)->toBe('tolower');
    $item02 = narrow(FunctionCallExpression::class, $item0->arguments[0]);
    $item03 = narrow(FunctionCallExpression::class, $item0->arguments[0]);
    expect($item03->name)->toBe('cast');
});
