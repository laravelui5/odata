<?php

declare(strict_types=1);

use LaravelUi5\OData\Driver\Sql\EloquentEntitySetResolver;
use LaravelUi5\OData\Driver\Sql\SqlEntitySetResolver;
use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Builder\ResolverMapBuilder;
use LaravelUi5\OData\Service\Builder\RuntimeSchemaBuilder;
use LaravelUi5\OData\Service\Contracts\EntitySetSourceInterface;
use LaravelUi5\OData\Service\Resolver\EloquentBinding;
use LaravelUi5\OData\Service\Resolver\ResolverMap;
use LaravelUi5\OData\Service\Resolver\SqlBinding;
use LaravelUi5\OData\Service\Resolver\SqlSourceBinding;
use LaravelUi5\OData\Tests\TestCase;

uses(TestCase::class);

// ── Helpers ──────────────────────────────────────────────────────────────────

function buildResolverTestEdmx(): \LaravelUi5\OData\Edm\Contracts\EdmxInterface
{
    $int32  = new PrimitiveType(EdmPrimitiveType::Int32);
    $string = new PrimitiveType(EdmPrimitiveType::String);

    $flightType = new EntityType(
        namespace: 'Test.Ns',
        name: 'Flight',
        key: [new Property('id', $int32)],
        declaredProperties: [new Property('id', $int32), new Property('origin', $string)],
    );
    $flightSet = new EntitySet('Flights', $flightType);

    $viewType = new EntityType(
        namespace: 'Test.Ns',
        name: 'ValueHelp',
        key: [new Property('id', $int32)],
        declaredProperties: [new Property('id', $int32), new Property('name', $string)],
    );
    $viewSet = new EntitySet('ValueHelp', $viewType);

    return (new EdmBuilder())
        ->namespace('Test.Ns')
        ->addEntityType($flightType)
        ->addEntityType($viewType)
        ->addEntitySet($flightSet)
        ->addEntitySet($viewSet)
        ->build();
}

// ── Binding classes ──────────────────────────────────────────────────────────

describe('EloquentBinding', function () {
    it('creates an EloquentEntitySetResolver', function () {
        $binding = new EloquentBinding(Flight::class);
        $resolver = $binding->createResolver();

        expect($resolver)->toBeInstanceOf(EloquentEntitySetResolver::class);
    });

    it('stores the model class', function () {
        $binding = new EloquentBinding(Flight::class);

        expect($binding->modelClass)->toBe(Flight::class);
    });
});

describe('SqlBinding', function () {
    it('creates a SqlEntitySetResolver', function () {
        $binding = new SqlBinding('my_view');
        $resolver = $binding->createResolver();

        expect($resolver)->toBeInstanceOf(SqlEntitySetResolver::class);
    });

    it('stores table and connection', function () {
        $binding = new SqlBinding('my_view', 'reporting');

        expect($binding->table)->toBe('my_view')
            ->and($binding->connection)->toBe('reporting');
    });

    it('defaults connection to null', function () {
        $binding = new SqlBinding('my_view');

        expect($binding->connection)->toBeNull();
    });
});

describe('SqlSourceBinding', function () {
    it('stores the source class', function () {
        $binding = new SqlSourceBinding('App\\Sources\\MySource');

        expect($binding->sourceClass)->toBe('App\\Sources\\MySource');
    });

    it('creates a SqlEntitySetResolver', function () {
        // Bind a mock source in the container
        app()->bind(TestEntitySetSource::class, fn () => new TestEntitySetSource());

        $binding = new SqlSourceBinding(TestEntitySetSource::class);
        $resolver = $binding->createResolver();

        expect($resolver)->toBeInstanceOf(SqlEntitySetResolver::class);
    });
});

// ── ResolverMapBuilder ───────────────────────────────────────────────────────

describe('ResolverMapBuilder', function () {
    it('registers eloquent bindings by entity set', function () {
        $edmx = buildResolverTestEdmx();
        $builder = new ResolverMapBuilder($edmx);
        $set = $edmx->getEntityContainer()->getEntitySet('Flights');

        $builder->eloquent($set, Flight::class);
        $bindings = $builder->getBindings();

        expect($bindings)->toHaveKey('Flights')
            ->and($bindings['Flights'])->toBeInstanceOf(EloquentBinding::class)
            ->and($bindings['Flights']->modelClass)->toBe(Flight::class);
    });

    it('registers sql bindings by entity set', function () {
        $edmx = buildResolverTestEdmx();
        $builder = new ResolverMapBuilder($edmx);
        $set = $edmx->getEntityContainer()->getEntitySet('ValueHelp');

        $builder->sql($set, 'value_help_view', 'reporting');
        $bindings = $builder->getBindings();

        expect($bindings)->toHaveKey('ValueHelp')
            ->and($bindings['ValueHelp'])->toBeInstanceOf(SqlBinding::class)
            ->and($bindings['ValueHelp']->table)->toBe('value_help_view')
            ->and($bindings['ValueHelp']->connection)->toBe('reporting');
    });

    it('registers sql source bindings by entity set', function () {
        $edmx = buildResolverTestEdmx();
        $builder = new ResolverMapBuilder($edmx);
        $set = $edmx->getEntityContainer()->getEntitySet('ValueHelp');

        $builder->sqlSource($set, TestEntitySetSource::class);
        $bindings = $builder->getBindings();

        expect($bindings)->toHaveKey('ValueHelp')
            ->and($bindings['ValueHelp'])->toBeInstanceOf(SqlSourceBinding::class)
            ->and($bindings['ValueHelp']->sourceClass)->toBe(TestEntitySetSource::class);
    });

    it('builds a frozen ResolverMap', function () {
        $edmx = buildResolverTestEdmx();
        $builder = new ResolverMapBuilder($edmx);
        $set = $edmx->getEntityContainer()->getEntitySet('Flights');

        $builder->eloquent($set, Flight::class);
        $map = $builder->build();

        expect($map)->toBeInstanceOf(ResolverMap::class)
            ->and($map->getBindings())->toHaveKey('Flights');
    });

    it('supports fluent chaining', function () {
        $edmx = buildResolverTestEdmx();
        $builder = new ResolverMapBuilder($edmx);
        $container = $edmx->getEntityContainer();

        $result = $builder
            ->eloquent($container->getEntitySet('Flights'), Flight::class)
            ->sql($container->getEntitySet('ValueHelp'), 'vh_view');

        expect($result)->toBe($builder)
            ->and($builder->getBindings())->toHaveCount(2);
    });

    it('exposes the Edmx', function () {
        $edmx = buildResolverTestEdmx();
        $builder = new ResolverMapBuilder($edmx);

        expect($builder->getEdmx())->toBe($edmx);
    });
});

// ── ResolverMap ──────────────────────────────────────────────────────────────

describe('ResolverMap', function () {
    it('applies bindings to a RuntimeSchemaBuilder', function () {
        $edmx = buildResolverTestEdmx();

        $map = new ResolverMap([
            'Flights' => new EloquentBinding(Flight::class),
            'ValueHelp' => new SqlBinding('value_help_view'),
        ]);

        $runtimeBuilder = new RuntimeSchemaBuilder($edmx);
        $map->applyTo($runtimeBuilder);
        $schema = $runtimeBuilder->build();

        $container = $schema->getEdmx()->getEntityContainer();

        expect($schema->getResolver($container->getEntitySet('Flights')))
            ->toBeInstanceOf(EloquentEntitySetResolver::class)
            ->and($schema->getResolver($container->getEntitySet('ValueHelp')))
            ->toBeInstanceOf(SqlEntitySetResolver::class);
    });

    it('skips bindings for unknown entity set names', function () {
        $edmx = buildResolverTestEdmx();

        $map = new ResolverMap([
            'Flights' => new EloquentBinding(Flight::class),
            'ValueHelp' => new SqlBinding('vh_view'),
            'NonExistent' => new EloquentBinding(Flight::class),
        ]);

        $runtimeBuilder = new RuntimeSchemaBuilder($edmx);
        $map->applyTo($runtimeBuilder);

        // Should not throw — NonExistent is silently skipped
        $schema = $runtimeBuilder->build();
        expect($schema)->not->toBeNull();
    });

    it('returns its bindings', function () {
        $bindings = [
            'Flights' => new EloquentBinding(Flight::class),
        ];

        $map = new ResolverMap($bindings);

        expect($map->getBindings())->toBe($bindings);
    });
});

// ── Test fixture ─────────────────────────────────────────────────────────────

class TestEntitySetSource implements EntitySetSourceInterface
{
    public function query(): \Illuminate\Database\Query\Builder
    {
        return \Illuminate\Support\Facades\DB::table('flights');
    }
}
