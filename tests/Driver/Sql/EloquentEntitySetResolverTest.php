<?php

declare(strict_types=1);

use LaravelUi5\OData\Driver\Sql\EloquentEntitySetResolver;
use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Protocol\Planning\EntitySetQueryPlan;
use LaravelUi5\OData\Protocol\Planning\ExpandList;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;
use LaravelUi5\OData\Protocol\Planning\OrderByItem;
use LaravelUi5\OData\Protocol\Planning\OrderByList;
use LaravelUi5\OData\Protocol\Planning\OrderDirection;
use LaravelUi5\OData\Protocol\Planning\PropertySelectItem;
use LaravelUi5\OData\Protocol\Planning\SelectList;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Tests\TestCase;

/**
 * Tier-3 Pest tests for EloquentEntitySetResolver.
 *
 * Boots Orchestra TestCase (SQLite in-memory) so that the flights table and
 * real Eloquent queries are available. Does NOT make HTTP requests.
 */
uses(TestCase::class)
    ->beforeEach(function () {
        Flight::insert([
            ['origin' => 'lhr', 'destination' => 'lax', 'gate' => 1, 'duration' => 41100],
            ['origin' => 'sfo', 'destination' => 'lax', 'gate' => 2, 'duration' => 2133],
            ['origin' => 'jfk', 'destination' => 'ord', 'gate' => 3, 'duration' => 3600],
        ]);
    });

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Build a minimal EntitySet + property objects for the flights table.
 * Returns [$flightSet, $originProp, $destinationProp, $idProp].
 */
function flightFixture(): array
{
    $int32  = new PrimitiveType(EdmPrimitiveType::Int32);
    $string = new PrimitiveType(EdmPrimitiveType::String);

    $idProp          = new Property('id', $int32);
    $originProp      = new Property('origin', $string);
    $destinationProp = new Property('destination', $string);

    $flightType = new EntityType(
        namespace: 'Test.Ns',
        name: 'Flight',
        key: [$idProp],
        declaredProperties: [$idProp, $originProp, $destinationProp],
    );

    $flightSet = new EntitySet('Flights', $flightType);

    return [$flightSet, $originProp, $destinationProp, $idProp];
}

function makePlan(
    ?\LaravelUi5\OData\Protocol\Planning\Expression\FilterExpression $filter = null,
    SelectList $select = new SelectList(),
    OrderByList $orderBy = new OrderByList(),
    ?int $top = null,
    ?int $skip = null,
): EntitySetQueryPlan {
    [$flightSet] = flightFixture();

    return new EntitySetQueryPlan(
        target:    $flightSet,
        filter:    $filter,
        select:    $select,
        expand:    new ExpandList(),
        orderBy:   $orderBy,
        top:       $top,
        skip:      $skip,
        skipToken: null,
        count:     false,
    );
}

function resolver(): EloquentEntitySetResolver
{
    return new EloquentEntitySetResolver(Flight::class);
}

function resolveAll(EntitySetQueryPlan $plan): array
{
    return iterator_to_array(resolver()->resolve($plan), false);
}

// ── Tests ─────────────────────────────────────────────────────────────────────

it('yields all rows when no filter is applied', function () {
    $rows = resolveAll(makePlan());
    expect($rows)->toHaveCount(3);
});

it('filters rows with an eq expression on a string column', function () {
    [, $originProp] = flightFixture();

    $filter = new BinaryExpression(
        new PropertyPathExpression([$originProp]),
        BinaryOperator::Eq,
        new LiteralExpression('lhr', 'Edm.String'),
    );

    $rows = resolveAll(makePlan(filter: $filter));
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['origin'])->toBe('lhr');
});

it('filters rows with a ne expression', function () {
    [, $originProp] = flightFixture();

    $filter = new BinaryExpression(
        new PropertyPathExpression([$originProp]),
        BinaryOperator::Ne,
        new LiteralExpression('lhr', 'Edm.String'),
    );

    $rows = resolveAll(makePlan(filter: $filter));
    expect($rows)->toHaveCount(2);
});

it('filters rows with an and expression', function () {
    [, $originProp, $destinationProp] = flightFixture();

    $filter = new BinaryExpression(
        new BinaryExpression(
            new PropertyPathExpression([$originProp]),
            BinaryOperator::Ne,
            new LiteralExpression('jfk', 'Edm.String'),
        ),
        BinaryOperator::And,
        new BinaryExpression(
            new PropertyPathExpression([$destinationProp]),
            BinaryOperator::Eq,
            new LiteralExpression('lax', 'Edm.String'),
        ),
    );

    $rows = resolveAll(makePlan(filter: $filter));
    expect($rows)->toHaveCount(2);
});

it('filters rows with an or expression', function () {
    [, $originProp] = flightFixture();

    $filter = new BinaryExpression(
        new BinaryExpression(
            new PropertyPathExpression([$originProp]),
            BinaryOperator::Eq,
            new LiteralExpression('lhr', 'Edm.String'),
        ),
        BinaryOperator::Or,
        new BinaryExpression(
            new PropertyPathExpression([$originProp]),
            BinaryOperator::Eq,
            new LiteralExpression('jfk', 'Edm.String'),
        ),
    );

    $rows = resolveAll(makePlan(filter: $filter));
    expect($rows)->toHaveCount(2);
});

it('restricts columns when a select list is given', function () {
    [, $originProp] = flightFixture();

    $select = new SelectList([new PropertySelectItem($originProp)]);

    $rows = resolveAll(makePlan(select: $select));
    expect($rows)->toHaveCount(3)
        ->and(array_keys($rows[0]))->toBe(['origin']);
});

it('orders rows ascending', function () {
    [, $originProp] = flightFixture();

    $orderBy = new OrderByList([
        new OrderByItem(new PropertyPathExpression([$originProp]), OrderDirection::Asc),
    ]);

    $rows = resolveAll(makePlan(orderBy: $orderBy));
    $origins = array_column($rows, 'origin');
    expect($origins)->toBe(['jfk', 'lhr', 'sfo']);
});

it('orders rows descending', function () {
    [, $originProp] = flightFixture();

    $orderBy = new OrderByList([
        new OrderByItem(new PropertyPathExpression([$originProp]), OrderDirection::Desc),
    ]);

    $rows = resolveAll(makePlan(orderBy: $orderBy));
    $origins = array_column($rows, 'origin');
    expect($origins)->toBe(['sfo', 'lhr', 'jfk']);
});

it('applies top to limit results', function () {
    $rows = resolveAll(makePlan(top: 2));
    expect($rows)->toHaveCount(2);
});

it('applies skip to offset results', function () {
    $rows = resolveAll(makePlan(skip: 1));
    expect($rows)->toHaveCount(2);
});

it('applies top and skip together', function () {
    [, $originProp] = flightFixture();

    $orderBy = new OrderByList([
        new OrderByItem(new PropertyPathExpression([$originProp]), OrderDirection::Asc),
    ]);

    $rows = resolveAll(makePlan(orderBy: $orderBy, top: 1, skip: 1));
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['origin'])->toBe('lhr');
});

it('filters null column values with eq null', function () {
    // Insert a row with null origin
    Flight::insert(['origin' => null, 'destination' => 'xxx', 'gate' => 9, 'duration' => 100]);

    [, $originProp] = flightFixture();
    $filter = new BinaryExpression(
        new PropertyPathExpression([$originProp]),
        BinaryOperator::Eq,
        new \LaravelUi5\OData\Protocol\Planning\Expression\NullLiteralExpression(),
    );

    $rows = resolveAll(makePlan(filter: $filter));
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['destination'])->toBe('xxx');
});
