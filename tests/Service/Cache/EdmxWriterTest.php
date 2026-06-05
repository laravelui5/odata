<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\Container\FunctionImport;
use LaravelUi5\OData\Edm\Container\NavigationPropertyBinding;
use LaravelUi5\OData\Edm\Container\Singleton;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\EdmFunction;
use LaravelUi5\OData\Edm\FunctionParameter;
use LaravelUi5\OData\Edm\Property\NavigationProperty;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Cache\EdmxWriter;

/**
 * Build a test EdmxInterface with entity types, navigation, functions, singletons.
 */
function buildTestEdmx(): EdmxInterface
{
    $int32  = new PrimitiveType(EdmPrimitiveType::Int32);
    $string = new PrimitiveType(EdmPrimitiveType::String);

    $flightId    = new Property('id', $int32);
    $originProp  = new Property('origin', $string);
    $destProp    = new Property('destination', $string);

    $passengerId   = new Property('id', $int32);
    $passengerName = new Property('name', $string);
    $passengerFk   = new Property('flight_id', $int32);

    $passengerType = new EntityType(
        namespace: 'Test.Ns',
        name: 'Passenger',
        key: [$passengerId],
        declaredProperties: [$passengerId, $passengerName, $passengerFk],
    );

    $flightType = new EntityType(
        namespace: 'Test.Ns',
        name: 'Flight',
        key: [$flightId],
        declaredProperties: [$flightId, $originProp, $destProp],
        declaredNavigationProperties: [
            new NavigationProperty(
                name: 'passengers',
                targetType: $passengerType,
                isCollection: true,
            ),
        ],
    );

    $flightSet = new EntitySet(
        name: 'Flights',
        entityType: $flightType,
        navigationPropertyBindings: [
            new NavigationPropertyBinding('passengers', 'Passengers'),
        ],
    );

    $passengerSet = new EntitySet(
        name: 'Passengers',
        entityType: $passengerType,
    );

    $countFunc = new EdmFunction(name: 'GetFlightCount', returnType: $int32);
    $byOriginFunc = new EdmFunction(
        name: 'GetFlightsByOrigin',
        returnType: $int32,
        parameters: [new FunctionParameter('origin', $string)],
    );

    return (new EdmBuilder())
        ->namespace('Test.Ns')
        ->addEntityType($flightType)
        ->addEntityType($passengerType)
        ->addEntitySet($flightSet)
        ->addEntitySet($passengerSet)
        ->addSingleton(new Singleton('DefaultFlight', $flightType))
        ->addFunction($countFunc)
        ->addFunction($byOriginFunc)
        ->addFunctionImport(new FunctionImport('GetFlightCount', $countFunc))
        ->addFunctionImport(new FunctionImport('GetFlightsByOrigin', $byOriginFunc))
        ->build();
}

/**
 * Helper: write cache, autoload generated classes, return loaded EdmxInterface.
 */
function writeAndLoad(EdmxInterface $edmx, string $tmpDir): EdmxInterface
{
    $namespace = 'EdmxWriterTest\\Edm';
    $outputDir = $tmpDir . '/Edm';

    $writer = new EdmxWriter($edmx, $outputDir, $namespace);
    $writer->write();

    // Register a PSR-4 autoloader for the generated classes
    spl_autoload_register(function (string $class) use ($namespace, $outputDir) {
        if (!str_starts_with($class, $namespace . '\\')) {
            return;
        }
        $relative = str_replace('\\', '/', substr($class, strlen($namespace) + 1));
        $file = $outputDir . '/' . $relative . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });

    // Also autoload the root Edmx class
    $edmxFile = $outputDir . '/Edmx.php';
    require_once $edmxFile;

    $edmxClass = $namespace . '\\Edmx';
    return new $edmxClass();
}

beforeEach(function () {
    $this->tmpDir = sys_get_temp_dir() . '/edmx_writer_test_' . getmypid();
    if (!is_dir($this->tmpDir)) {
        mkdir($this->tmpDir, 0755, true);
    }
});

afterEach(function () {
    // Clean up temp directory
    if (is_dir($this->tmpDir)) {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tmpDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->tmpDir);
    }
});

it('generates Edmx.php that implements EdmxInterface', function () {
    $original = buildTestEdmx();
    $loaded = writeAndLoad($original, $this->tmpDir);

    expect($loaded)->toBeInstanceOf(EdmxInterface::class);
    expect($loaded->getVersion())->toBe('4.0');
});

it('preserves entity container name', function () {
    $original = buildTestEdmx();
    $loaded = writeAndLoad($original, $this->tmpDir);

    expect($loaded->getEntityContainer()->getName())
        ->toBe($original->getEntityContainer()->getName());
});

it('preserves entity sets', function () {
    $original = buildTestEdmx();
    $loaded = writeAndLoad($original, $this->tmpDir);

    $container = $loaded->getEntityContainer();
    expect($container->getEntitySets())->toHaveCount(2);
    expect($container->getEntitySet('Flights'))->not->toBeNull();
    expect($container->getEntitySet('Passengers'))->not->toBeNull();
});

it('preserves entity type properties', function () {
    $original = buildTestEdmx();
    $loaded = writeAndLoad($original, $this->tmpDir);

    $flightSet = $loaded->getEntityContainer()->getEntitySet('Flights');
    $flightType = $flightSet->getEntityType();

    expect($flightType->getName())->toBe('Flight');
    expect($flightType->getQualifiedName())->toBe('Test.Ns.Flight');
    expect($flightType->getDeclaredProperties())->toHaveCount(3);
    expect($flightType->getProperty('id'))->not->toBeNull();
    expect($flightType->getProperty('origin'))->not->toBeNull();
    expect($flightType->getProperty('destination'))->not->toBeNull();
});

it('preserves property types as PrimitiveType', function () {
    $original = buildTestEdmx();
    $loaded = writeAndLoad($original, $this->tmpDir);

    $flightType = $loaded->getEntityContainer()->getEntitySet('Flights')->getEntityType();
    $idProp = $flightType->getProperty('id');

    expect($idProp->getType()->getQualifiedName())->toBe('Edm.Int32');
});

it('preserves key properties', function () {
    $original = buildTestEdmx();
    $loaded = writeAndLoad($original, $this->tmpDir);

    $flightType = $loaded->getEntityContainer()->getEntitySet('Flights')->getEntityType();
    $key = $flightType->getKey();

    expect($key)->toHaveCount(1);
    expect($key[0]->getName())->toBe('id');
});

it('preserves navigation properties', function () {
    $original = buildTestEdmx();
    $loaded = writeAndLoad($original, $this->tmpDir);

    $flightType = $loaded->getEntityContainer()->getEntitySet('Flights')->getEntityType();
    $nav = $flightType->getNavigationProperty('passengers');

    expect($nav)->not->toBeNull();
    expect($nav->isCollection())->toBeTrue();
    expect($nav->getTargetType()->getName())->toBe('Passenger');
});

it('preserves navigation property bindings', function () {
    $original = buildTestEdmx();
    $loaded = writeAndLoad($original, $this->tmpDir);

    $flightSet = $loaded->getEntityContainer()->getEntitySet('Flights');
    $binding = $flightSet->getNavigationPropertyBinding('passengers');

    expect($binding)->not->toBeNull();
    expect($binding->getTarget())->toBe('Passengers');
});

it('preserves function imports', function () {
    $original = buildTestEdmx();
    $loaded = writeAndLoad($original, $this->tmpDir);

    $container = $loaded->getEntityContainer();
    expect($container->getFunctionImports())->toHaveCount(2);

    $countImport = $container->getFunctionImport('GetFlightCount');
    expect($countImport)->not->toBeNull();
    expect($countImport->getFunction()->getName())->toBe('GetFlightCount');

    $byOriginImport = $container->getFunctionImport('GetFlightsByOrigin');
    expect($byOriginImport)->not->toBeNull();
    expect($byOriginImport->getFunction()->getParameters())->toHaveCount(1);
    expect($byOriginImport->getFunction()->getParameter('origin'))->not->toBeNull();
});

it('preserves singletons', function () {
    $original = buildTestEdmx();
    $loaded = writeAndLoad($original, $this->tmpDir);

    $singleton = $loaded->getEntityContainer()->getSingleton('DefaultFlight');
    expect($singleton)->not->toBeNull();
    expect($singleton->getEntityType()->getName())->toBe('Flight');
});

it('preserves schema with entity types', function () {
    $original = buildTestEdmx();
    $loaded = writeAndLoad($original, $this->tmpDir);

    $schema = $loaded->getSchema('Test.Ns');
    expect($schema)->not->toBeNull();
    expect($schema->getNamespace())->toBe('Test.Ns');
    expect($schema->getEntityTypes())->toHaveCount(2);
    expect($schema->getEntityType('Flight'))->not->toBeNull();
    expect($schema->getEntityType('Passenger'))->not->toBeNull();
});

it('preserves schema functions', function () {
    $original = buildTestEdmx();
    $loaded = writeAndLoad($original, $this->tmpDir);

    $schema = $loaded->getSchema('Test.Ns');
    $functions = $schema->getFunctions();
    expect($functions)->toHaveCount(2);
});

it('generates directory structure with Types/ and Entities/', function () {
    $original = buildTestEdmx();
    writeAndLoad($original, $this->tmpDir);

    $edmDir = $this->tmpDir . '/Edm';
    expect(is_file($edmDir . '/Edmx.php'))->toBeTrue();
    expect(is_file($edmDir . '/Types/Flight.php'))->toBeTrue();
    expect(is_file($edmDir . '/Types/Passenger.php'))->toBeTrue();
    expect(is_file($edmDir . '/Entities/Flights.php'))->toBeTrue();
    expect(is_file($edmDir . '/Entities/Passengers.php'))->toBeTrue();
});

it('generates a loadable entity set when the set name equals its type name', function () {
    // Regression (v1.0.5): writeEntitySet() used to emit `use {ns}\Types\{Name};`
    // alongside `class {Name}` in the same file. When a set's name equalled its
    // type's name the import and the declaration collided and PHP fataled at
    // autoload with "Cannot redeclare class …". Production hit this on MyDraft /
    // MyOrganization / MyPortalState (2026-06-04). The fix references the type by
    // FQN and drops the self-import.
    $int32 = new PrimitiveType(EdmPrimitiveType::Int32);
    $id = new Property('id', $int32);

    // Set name === type name — the collision case.
    $type = new EntityType(
        namespace: 'Test.Ns',
        name: 'MyDraft',
        key: [$id],
        declaredProperties: [$id],
    );
    $set = new EntitySet(name: 'MyDraft', entityType: $type);

    $edmx = (new EdmBuilder())
        ->namespace('Test.Ns')
        ->addEntityType($type)
        ->addEntitySet($set)
        ->build();

    // Use a unique namespace so the loaded classes don't collide with other tests'.
    $namespace = 'EdmxWriterCollisionTest\\Edm';
    $outputDir = $this->tmpDir . '/Edm';
    (new EdmxWriter($edmx, $outputDir, $namespace))->write();

    // The entity-set file must not import its own type (the old collision line).
    $entitySrc = file_get_contents($outputDir . '/Entities/MyDraft.php');
    expect($entitySrc)->not->toContain('use ' . $namespace . '\\Types\\MyDraft;');

    // And it must actually load + resolve its type without a redeclare fatal.
    require_once $outputDir . '/Types/MyDraft.php';
    require_once $outputDir . '/Entities/MyDraft.php';

    $setClass = $namespace . '\\Entities\\MyDraft';
    $loadedSet = new $setClass();

    expect($loadedSet->getName())->toBe('MyDraft');
    expect($loadedSet->getEntityType()->getName())->toBe('MyDraft');
});
