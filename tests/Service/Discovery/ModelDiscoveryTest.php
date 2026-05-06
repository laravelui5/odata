<?php

declare(strict_types=1);

use LaravelUi5\OData\Driver\Sql\EloquentEntitySetResolver;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Fixtures\DiscoveryFlightService;
use LaravelUi5\OData\Fixtures\DiscoveryWithManualService;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Fixtures\Models\Passenger;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataEntity;
use LaravelUi5\OData\Service\Discovery\ModelDiscovery;
use LaravelUi5\OData\Tests\TestCase;

uses(TestCase::class);

// ── Helpers ──────────────────────────────────────────────────────────────────

function buildEdmx(string $namespace, array $modelClasses): \LaravelUi5\OData\Edm\Contracts\EdmxInterface
{
    $discovery = new ModelDiscovery();
    foreach ($modelClasses as $class) {
        $discovery->add($class);
    }

    $builder = (new EdmBuilder())->namespace($namespace);
    $discovery->apply($builder, $namespace);

    return $builder->build();
}

// ── Entity Type Discovery ────────────────────────────────────────────────────

describe('ModelDiscovery → entity type', function () {
    it('discovers entity type from Flight model', function () {
        $edmx = buildEdmx('Test.Ns', [Flight::class]);
        $schema = $edmx->getSchemas()['Test.Ns'];

        $flightType = $schema->getEntityTypes()[0];

        expect($flightType->getName())->toBe('Flight')
            ->and($flightType->getQualifiedName())->toBe('Test.Ns.Flight');
    });

    it('uses model key as OData key', function () {
        $edmx = buildEdmx('Test.Ns', [Flight::class]);
        $flightType = $edmx->getSchemas()['Test.Ns']->getEntityTypes()[0];
        $key = $flightType->getKey();

        expect($key)->toHaveCount(1)
            ->and($key[0]->getName())->toBe('id');
    });

    it('discovers all structural properties from DB columns', function () {
        $edmx = buildEdmx('Test.Ns', [Flight::class]);
        $flightType = $edmx->getSchemas()['Test.Ns']->getEntityTypes()[0];
        $propNames = array_map(fn ($p) => $p->getName(), $flightType->getDeclaredProperties());

        expect($propNames)->toContain('id', 'origin', 'destination', 'gate', 'duration');
    });
});

// ── Type Mapping ─────────────────────────────────────────────────────────────

describe('ModelDiscovery → type mapping', function () {
    it('maps integer key to Int32 on SQLite', function () {
        // SQLite normalizes bigIncrements to integer
        $edmx = buildEdmx('Test.Ns', [Flight::class]);
        $flightType = $edmx->getSchemas()['Test.Ns']->getEntityTypes()[0];
        $idProp = $flightType->getProperty('id');

        expect($idProp->getType())->toBeInstanceOf(PrimitiveType::class)
            ->and($idProp->getType()->getPrimitiveType())->toBe(EdmPrimitiveType::Int32);
    });

    it('maps string column to String', function () {
        $edmx = buildEdmx('Test.Ns', [Flight::class]);
        $flightType = $edmx->getSchemas()['Test.Ns']->getEntityTypes()[0];
        $originProp = $flightType->getProperty('origin');

        expect($originProp->getType()->getPrimitiveType())->toBe(EdmPrimitiveType::String);
    });

    it('casts override DB type (float cast on duration)', function () {
        $edmx = buildEdmx('Test.Ns', [Flight::class]);
        $flightType = $edmx->getSchemas()['Test.Ns']->getEntityTypes()[0];
        $durationProp = $flightType->getProperty('duration');

        expect($durationProp->getType()->getPrimitiveType())->toBe(EdmPrimitiveType::Double);
    });

    it('casts integer override maps to Int32', function () {
        $edmx = buildEdmx('Test.Ns', [Passenger::class]);
        $passengerType = $edmx->getSchemas()['Test.Ns']->getEntityTypes()[0];
        $flightIdProp = $passengerType->getProperty('flight_id');

        expect($flightIdProp->getType()->getPrimitiveType())->toBe(EdmPrimitiveType::Int32);
    });

    it('casts boolean maps to Boolean', function () {
        $edmx = buildEdmx('Test.Ns', [Passenger::class]);
        $passengerType = $edmx->getSchemas()['Test.Ns']->getEntityTypes()[0];
        $chipsProp = $passengerType->getProperty('chips');

        expect($chipsProp->getType()->getPrimitiveType())->toBe(EdmPrimitiveType::Boolean);
    });

    it('casts datetime with format maps to DateTimeOffset', function () {
        $edmx = buildEdmx('Test.Ns', [Passenger::class]);
        $passengerType = $edmx->getSchemas()['Test.Ns']->getEntityTypes()[0];
        $openTimeProp = $passengerType->getProperty('open_time');

        expect($openTimeProp->getType()->getPrimitiveType())->toBe(EdmPrimitiveType::DateTimeOffset);
    });

    it('casts array maps to String', function () {
        $edmx = buildEdmx('Test.Ns', [Passenger::class]);
        $passengerType = $edmx->getSchemas()['Test.Ns']->getEntityTypes()[0];
        $emailsProp = $passengerType->getProperty('emails');

        expect($emailsProp->getType()->getPrimitiveType())->toBe(EdmPrimitiveType::String);
    });
});

// ── Entity Set ───────────────────────────────────────────────────────────────

describe('ModelDiscovery → entity set', function () {
    it('creates entity set with pluralized name', function () {
        $edmx = buildEdmx('Test.Ns', [Flight::class]);

        expect($edmx->getEntityContainer()->getEntitySet('Flights'))->not->toBeNull();
    });

    it('entity set references the correct entity type', function () {
        $edmx = buildEdmx('Test.Ns', [Flight::class]);
        $set = $edmx->getEntityContainer()->getEntitySet('Flights');

        expect($set->getEntityType()->getName())->toBe('Flight');
    });
});

// ── Navigation Properties ────────────────────────────────────────────────────

describe('ModelDiscovery → navigation properties', function () {
    it('discovers hasMany as collection navigation property', function () {
        $edmx = buildEdmx('Test.Ns', [Flight::class, Passenger::class]);
        $schema = $edmx->getSchemas()['Test.Ns'];
        $flightType = collect($schema->getEntityTypes())->first(fn (EntityTypeInterface $t) => $t->getName() === 'Flight');

        $navProp = $flightType->getNavigationProperty('passengers');

        expect($navProp)->not->toBeNull()
            ->and($navProp->isCollection())->toBeTrue()
            ->and($navProp->getTargetType()->getName())->toBe('Passenger');
    });

    it('discovers belongsTo as single-valued navigation property', function () {
        $edmx = buildEdmx('Test.Ns', [Flight::class, Passenger::class]);
        $schema = $edmx->getSchemas()['Test.Ns'];
        $passengerType = collect($schema->getEntityTypes())->first(fn (EntityTypeInterface $t) => $t->getName() === 'Passenger');

        $navProp = $passengerType->getNavigationProperty('flight');

        expect($navProp)->not->toBeNull()
            ->and($navProp->isCollection())->toBeFalse()
            ->and($navProp->getTargetType()->getName())->toBe('Flight');
    });

    it('skips hasOneThrough relationships', function () {
        $edmx = buildEdmx('Test.Ns', [Passenger::class, Flight::class]);
        $schema = $edmx->getSchemas()['Test.Ns'];
        $passengerType = collect($schema->getEntityTypes())->first(fn (EntityTypeInterface $t) => $t->getName() === 'Passenger');

        expect($passengerType->getNavigationProperty('originAirport'))->toBeNull()
            ->and($passengerType->getNavigationProperty('destinationAirport'))->toBeNull();
    });

    it('only wires nav props when target model is discovered', function () {
        $edmx = buildEdmx('Test.Ns', [Flight::class]);
        $flightType = $edmx->getSchemas()['Test.Ns']->getEntityTypes()[0];

        expect($flightType->getDeclaredNavigationProperties())->toBeEmpty();
    });

    it('creates navigation property bindings on entity set', function () {
        $edmx = buildEdmx('Test.Ns', [Flight::class, Passenger::class]);
        $flightSet = $edmx->getEntityContainer()->getEntitySet('Flights');

        $binding = $flightSet->getNavigationPropertyBinding('passengers');

        expect($binding)->not->toBeNull()
            ->and($binding->getTarget())->toBe('Passengers');
    });
});

// ── Entity Set Map (for auto-resolver binding) ───────────────────────────────

describe('ModelDiscovery → entity set map', function () {
    it('returns entity set map for auto-resolver binding', function () {
        $discovery = new ModelDiscovery();
        $discovery->add(Flight::class);
        $discovery->add(Passenger::class);

        $builder = (new EdmBuilder())->namespace('Test.Ns');
        $discovery->apply($builder, 'Test.Ns');

        $map = $discovery->getEntitySetMap();

        expect($map)->toHaveKey('Flights', Flight::class)
            ->and($map)->toHaveKey('Passengers', Passenger::class);
    });
});

// ── Attribute Overrides ──────────────────────────────────────────────────────

describe('ModelDiscovery → attribute overrides', function () {
    it('applies ODataEntity name override', function () {
        $modelClass = get_class(new
            #[ODataEntity(name: 'Airplane', entitySet: 'Airplanes')]
            class extends Flight {
                protected $table = 'flights';
            }
        );

        $edmx = buildEdmx('Test.Ns', [$modelClass]);
        $schema = $edmx->getSchemas()['Test.Ns'];

        expect($schema->getEntityTypes()[0]->getName())->toBe('Airplane')
            ->and($edmx->getEntityContainer()->getEntitySet('Airplanes'))->not->toBeNull();
    });
});

// ── ODataService integration ─────────────────────────────────────────────────

describe('ODataService → discoverModel integration', function () {
    it('auto-binds resolvers for discovered models', function () {
        $service = new DiscoveryFlightService();
        $schema = $service->schema();
        $container = $schema->getEdmx()->getEntityContainer();

        expect($container->getEntitySet('Flights'))->not->toBeNull()
            ->and($container->getEntitySet('Passengers'))->not->toBeNull();

        $flightResolver = $schema->getResolver($container->getEntitySet('Flights'));
        expect($flightResolver)->toBeInstanceOf(EloquentEntitySetResolver::class);
    });

    it('coexists with manually defined entity types', function () {
        $service = new DiscoveryWithManualService();
        $schema = $service->schema();
        $container = $schema->getEdmx()->getEntityContainer();

        // Discovered model
        expect($container->getEntitySet('Flights'))->not->toBeNull();

        // Manually defined function
        expect($container->getFunctionImport('GetCount'))->not->toBeNull();
    });
});
