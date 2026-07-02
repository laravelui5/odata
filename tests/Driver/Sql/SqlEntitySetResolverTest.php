<?php

declare(strict_types=1);

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Driver\Sql\SqlEntitySetResolver;
use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Protocol\Planning\EntityQueryPlan;
use LaravelUi5\OData\Protocol\Planning\EntitySetQueryPlan;
use LaravelUi5\OData\Protocol\Planning\ExpandList;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\FunctionCallExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\NullLiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;
use LaravelUi5\OData\Protocol\Planning\KeyExpression;
use LaravelUi5\OData\Protocol\Planning\OrderByItem;
use LaravelUi5\OData\Protocol\Planning\OrderByList;
use LaravelUi5\OData\Protocol\Planning\OrderDirection;
use LaravelUi5\OData\Protocol\Planning\PropertySelectItem;
use LaravelUi5\OData\Protocol\Planning\SelectList;
use LaravelUi5\OData\Service\Contracts\EntitySetSourceInterface;
use LaravelUi5\OData\Tests\TestCase;

/**
 * Tier-3 Pest tests for SqlEntitySetResolver.
 *
 * Boots Orchestra TestCase (SQLite in-memory) and uses the flights table
 * as a stand-in for a database view. Does NOT make HTTP requests.
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

function sqlTableSource(string $table): EntitySetSourceInterface
{
    return new class($table) implements EntitySetSourceInterface {
        public function __construct(private readonly string $table) {}

        public function query(\LaravelUi5\OData\Http\CustomQueryOptions $options): Builder
        {
            return DB::table($this->table);
        }
    };
}

function sqlClosureSource(Closure $factory): EntitySetSourceInterface
{
    return new class($factory) implements EntitySetSourceInterface {
        public function __construct(private readonly Closure $factory) {}

        public function query(\LaravelUi5\OData\Http\CustomQueryOptions $options): Builder
        {
            return ($this->factory)();
        }
    };
}

function sqlFlightFixture(): array
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

function sqlMakePlan(
    ?\LaravelUi5\OData\Protocol\Planning\Expression\FilterExpression $filter = null,
    SelectList $select = new SelectList(),
    OrderByList $orderBy = new OrderByList(),
    ?int $top = null,
    ?int $skip = null,
    ?string $search = null,
    bool $count = false,
): EntitySetQueryPlan {
    [$flightSet] = sqlFlightFixture();

    return new EntitySetQueryPlan(
        target:    $flightSet,
        filter:    $filter,
        select:    $select,
        expand:    new ExpandList(),
        orderBy:   $orderBy,
        top:       $top,
        skip:      $skip,
        skipToken: null,
        count:     $count,
        search:    $search,
    );
}

function sqlResolver(): SqlEntitySetResolver
{
    return new SqlEntitySetResolver(sqlTableSource('flights'));
}

function sqlResolveAll(EntitySetQueryPlan $plan): array
{
    return iterator_to_array(sqlResolver()->resolve($plan), false);
}

// ── Collection queries ───────────────────────────────────────────────────────

it('resolves all rows from a table', function () {
    $rows = sqlResolveAll(sqlMakePlan());
    expect($rows)->toHaveCount(3);
});

it('resolves all rows from a closure source', function () {
    $resolver = new SqlEntitySetResolver(sqlClosureSource(fn () => DB::table('flights')));
    $rows = iterator_to_array($resolver->resolve(sqlMakePlan()), false);
    expect($rows)->toHaveCount(3);
});

it('applies $filter with eq', function () {
    [, $originProp] = sqlFlightFixture();

    $filter = new BinaryExpression(
        new PropertyPathExpression([$originProp]),
        BinaryOperator::Eq,
        new LiteralExpression('lhr', 'Edm.String'),
    );

    $rows = sqlResolveAll(sqlMakePlan(filter: $filter));
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['origin'])->toBe('lhr');
});

it('applies $filter with ne', function () {
    [, $originProp] = sqlFlightFixture();

    $filter = new BinaryExpression(
        new PropertyPathExpression([$originProp]),
        BinaryOperator::Ne,
        new LiteralExpression('lhr', 'Edm.String'),
    );

    $rows = sqlResolveAll(sqlMakePlan(filter: $filter));
    expect($rows)->toHaveCount(2);
});

it('applies $filter with and', function () {
    [, $originProp, $destinationProp] = sqlFlightFixture();

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

    $rows = sqlResolveAll(sqlMakePlan(filter: $filter));
    expect($rows)->toHaveCount(2);
});

it('applies $filter with or', function () {
    [, $originProp] = sqlFlightFixture();

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

    $rows = sqlResolveAll(sqlMakePlan(filter: $filter));
    expect($rows)->toHaveCount(2);
});

it('filters null with eq null', function () {
    Flight::insert(['origin' => null, 'destination' => 'xxx', 'gate' => 9, 'duration' => 100]);

    [, $originProp] = sqlFlightFixture();
    $filter = new BinaryExpression(
        new PropertyPathExpression([$originProp]),
        BinaryOperator::Eq,
        new NullLiteralExpression(),
    );

    $rows = sqlResolveAll(sqlMakePlan(filter: $filter));
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['destination'])->toBe('xxx');
});

it('applies $search on string columns', function () {
    $rows = sqlResolveAll(sqlMakePlan(search: 'lax'));
    // 'lax' matches destination of 2 flights
    expect($rows)->toHaveCount(2);
});

it('applies $select', function () {
    [, $originProp] = sqlFlightFixture();

    $select = new SelectList([new PropertySelectItem($originProp)]);

    $rows = sqlResolveAll(sqlMakePlan(select: $select));
    expect($rows)->toHaveCount(3)
        ->and(array_keys($rows[0]))->toBe(['origin']);
});

it('applies $orderby ascending', function () {
    [, $originProp] = sqlFlightFixture();

    $orderBy = new OrderByList([
        new OrderByItem(new PropertyPathExpression([$originProp]), OrderDirection::Asc),
    ]);

    $rows = sqlResolveAll(sqlMakePlan(orderBy: $orderBy));
    $origins = array_column($rows, 'origin');
    expect($origins)->toBe(['jfk', 'lhr', 'sfo']);
});

it('applies $orderby descending', function () {
    [, $originProp] = sqlFlightFixture();

    $orderBy = new OrderByList([
        new OrderByItem(new PropertyPathExpression([$originProp]), OrderDirection::Desc),
    ]);

    $rows = sqlResolveAll(sqlMakePlan(orderBy: $orderBy));
    $origins = array_column($rows, 'origin');
    expect($origins)->toBe(['sfo', 'lhr', 'jfk']);
});

it('applies $top', function () {
    $rows = sqlResolveAll(sqlMakePlan(top: 2));
    expect($rows)->toHaveCount(2);
});

it('applies $skip', function () {
    $rows = sqlResolveAll(sqlMakePlan(skip: 1));
    expect($rows)->toHaveCount(2);
});

it('applies $top and $skip together', function () {
    [, $originProp] = sqlFlightFixture();

    $orderBy = new OrderByList([
        new OrderByItem(new PropertyPathExpression([$originProp]), OrderDirection::Asc),
    ]);

    $rows = sqlResolveAll(sqlMakePlan(orderBy: $orderBy, top: 1, skip: 1));
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['origin'])->toBe('lhr');
});

// ── Count ────────────────────────────────────────────────────────────────────

it('returns count ignoring pagination', function () {
    $count = sqlResolver()->count(sqlMakePlan(top: 1));
    expect($count)->toBe(3);
});

it('returns count with filter applied', function () {
    [, $originProp] = sqlFlightFixture();

    $filter = new BinaryExpression(
        new PropertyPathExpression([$originProp]),
        BinaryOperator::Eq,
        new LiteralExpression('lhr', 'Edm.String'),
    );

    $count = sqlResolver()->count(sqlMakePlan(filter: $filter));
    expect($count)->toBe(1);
});

// ── Single entity ────────────────────────────────────────────────────────────

it('resolves single entity by key', function () {
    [, , , $idProp] = sqlFlightFixture();
    [$flightSet] = sqlFlightFixture();

    $plan = new EntityQueryPlan(
        target: $flightSet,
        key: new KeyExpression(['id' => new LiteralExpression(1, 'Edm.Int32')]),
        select: new SelectList(),
        expand: new ExpandList(),
    );

    $row = sqlResolver()->resolveOne($plan);
    expect($row)->not->toBeNull()
        ->and($row['origin'])->toBe('lhr');
});

it('returns null for missing entity', function () {
    [$flightSet] = sqlFlightFixture();

    $plan = new EntityQueryPlan(
        target: $flightSet,
        key: new KeyExpression(['id' => new LiteralExpression(999, 'Edm.Int32')]),
        select: new SelectList(),
        expand: new ExpandList(),
    );

    $row = sqlResolver()->resolveOne($plan);
    expect($row)->toBeNull();
});

// ── Closure query with joins ─────────────────────────────────────────────────

it('resolves from a closure with a join', function () {
    $resolver = new SqlEntitySetResolver(sqlClosureSource(fn () => DB::table('flights')
        ->join('passengers', 'flights.id', '=', 'passengers.flight_id')
        ->select('flights.origin', 'passengers.name')
    ));

    // Insert a passenger for the first flight
    \LaravelUi5\OData\Fixtures\Models\Passenger::insert([
        ['flight_id' => 1, 'name' => 'Alice'],
    ]);

    $rows = iterator_to_array($resolver->resolve(sqlMakePlan()), false);
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['origin'])->toBe('lhr')
        ->and($rows[0]['name'])->toBe('Alice');
});

// ── contains(tolower()) ─────────────────────────────────────────────────────

it('applies contains(tolower(prop), tolower(value))', function () {
    [, $originProp] = sqlFlightFixture();

    // Simulates: contains(tolower(origin), tolower('LH'))
    $filter = new FunctionCallExpression('contains', [
        new FunctionCallExpression('tolower', [
            new PropertyPathExpression([$originProp]),
        ]),
        new FunctionCallExpression('tolower', [
            new LiteralExpression('LH', 'Edm.String'),
        ]),
    ]);

    $rows = sqlResolveAll(sqlMakePlan(filter: $filter));
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['origin'])->toBe('lhr');
});

it('applies or of two contains(tolower()) filters', function () {
    [, $originProp, $destinationProp] = sqlFlightFixture();

    // Simulates: contains(tolower(origin), tolower('lh')) or contains(tolower(destination), tolower('la'))
    $filter = new BinaryExpression(
        new FunctionCallExpression('contains', [
            new FunctionCallExpression('tolower', [
                new PropertyPathExpression([$originProp]),
            ]),
            new FunctionCallExpression('tolower', [
                new LiteralExpression('lh', 'Edm.String'),
            ]),
        ]),
        BinaryOperator::Or,
        new FunctionCallExpression('contains', [
            new FunctionCallExpression('tolower', [
                new PropertyPathExpression([$destinationProp]),
            ]),
            new FunctionCallExpression('tolower', [
                new LiteralExpression('la', 'Edm.String'),
            ]),
        ]),
    );

    $rows = sqlResolveAll(sqlMakePlan(filter: $filter));
    // lhr→lax (matches both), sfo→lax (matches destination)
    expect($rows)->toHaveCount(2);
});

it('applies contains(tolower()) on a subquery source', function () {
    [, $originProp] = sqlFlightFixture();

    $resolver = new SqlEntitySetResolver(sqlClosureSource(fn () => DB::query()->fromSub(
        'SELECT id, origin, destination, gate, duration FROM flights WHERE gate <= 2',
        't'
    )));

    $filter = new FunctionCallExpression('contains', [
        new FunctionCallExpression('tolower', [
            new PropertyPathExpression([$originProp]),
        ]),
        new FunctionCallExpression('tolower', [
            new LiteralExpression('lh', 'Edm.String'),
        ]),
    ]);

    $rows = iterator_to_array($resolver->resolve(sqlMakePlan(filter: $filter)), false);
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['origin'])->toBe('lhr');
});
