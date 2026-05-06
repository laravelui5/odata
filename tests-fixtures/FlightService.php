<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\Container\FunctionImport;
use LaravelUi5\OData\Edm\Container\NavigationPropertyBinding;
use LaravelUi5\OData\Edm\Container\Singleton;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\EdmFunction;
use LaravelUi5\OData\Edm\FunctionParameter;
use LaravelUi5\OData\Edm\Property\NavigationProperty;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Protocol\Planning\FunctionInvocationPlan;
use LaravelUi5\OData\Service\Builder\ResolverMapBuilder;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\FunctionResolverInterface;
use LaravelUi5\OData\Service\Contracts\QueryPlanInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaBuilderInterface;
use LaravelUi5\OData\Service\Contracts\SingletonResolverInterface;
use LaravelUi5\OData\Fixtures\Models\Airport;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Fixtures\Models\Passenger;

/**
 * Reusable test fixture: an ODataService for the flights + passengers tables.
 *
 * Schema:
 *   Namespace : Test.Ns
 *   Entity set: Flights   (entityType: Test.Ns.Flight)   — id, origin, destination
 *   Entity set: Passengers (entityType: Test.Ns.Passenger) — id, name, flight_id
 *   Entity set: Airports  (entityType: Test.Ns.Airport)  — id, name, code
 *   Navigation: Flight → passengers (collection, HasMany)
 *   Navigation: Flight → airports (collection, BelongsToMany via pivot)
 *   Navigation: Airport → flights (collection, BelongsToMany via pivot)
 *   Function:   GetFlightCount() → Edm.Int32
 *   Function:   GetFlightsByOrigin(origin: Edm.String) → Edm.Int32
 */
final class FlightService extends ODataService
{
    public function serviceUri(): string
    {
        return '';
    }

    public function namespace(): string
    {
        return 'Test.Ns';
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverCustomEntitySet(FlightStats::class);

        $int32  = new PrimitiveType(EdmPrimitiveType::Int32);
        $string = new PrimitiveType(EdmPrimitiveType::String);

        // Flight entity type
        $flightId    = new Property('id', $int32);
        $originProp  = new Property('origin', $string);
        $destProp    = new Property('destination', $string);

        // Passenger entity type (declared first, but nav prop added after Flight exists)
        $passengerId       = new Property('id', $int32);
        $passengerName     = new Property('name', $string);
        $passengerFlightId = new Property('flight_id', $int32);

        // Airport entity type (for BelongsToMany testing)
        $airportId   = new Property('id', $int32);
        $airportName = new Property('name', $string);
        $airportCode = new Property('code', $string);

        // Forward-ref Airport type (used in Flight's nav prop)
        $airportTypeRef = new EntityType(
            namespace: 'Test.Ns',
            name: 'Airport',
            key: [$airportId],
            declaredProperties: [$airportId, $airportName, $airportCode],
        );

        // Flight entity type (needs Passenger and Airport types for nav props)
        $flightType = new EntityType(
            namespace: 'Test.Ns',
            name: 'Flight',
            key: [$flightId],
            declaredProperties: [$flightId, $originProp, $destProp],
            declaredNavigationProperties: [
                new NavigationProperty(
                    name: 'passengers',
                    targetType: new EntityType( // forward ref — resolved by name in container
                        namespace: 'Test.Ns',
                        name: 'Passenger',
                        key: [$passengerId],
                        declaredProperties: [$passengerId, $passengerName, $passengerFlightId],
                    ),
                    isCollection: true,
                ),
                new NavigationProperty(
                    name: 'airports',
                    targetType: $airportTypeRef,
                    isCollection: true,
                ),
            ],
        );

        // Now create Passenger type with nav back to Flight (single-valued)
        $passengerType = new EntityType(
            namespace: 'Test.Ns',
            name: 'Passenger',
            key: [$passengerId],
            declaredProperties: [$passengerId, $passengerName, $passengerFlightId],
            declaredNavigationProperties: [
                new NavigationProperty(
                    name: 'flight',
                    targetType: $flightType,
                    isCollection: false,
                ),
            ],
        );

        // Airport type with nav back to Flights (BelongsToMany)
        $airportType = new EntityType(
            namespace: 'Test.Ns',
            name: 'Airport',
            key: [$airportId],
            declaredProperties: [$airportId, $airportName, $airportCode],
            declaredNavigationProperties: [
                new NavigationProperty(
                    name: 'flights',
                    targetType: $flightType,
                    isCollection: true,
                ),
            ],
        );

        $flightSet = new EntitySet(
            name: 'Flights',
            entityType: $flightType,
            navigationPropertyBindings: [
                new NavigationPropertyBinding('passengers', 'Passengers'),
                new NavigationPropertyBinding('airports', 'Airports'),
            ],
        );
        $passengerSet = new EntitySet(
            name: 'Passengers',
            entityType: $passengerType,
            navigationPropertyBindings: [
                new NavigationPropertyBinding('flight', 'Flights'),
            ],
        );
        $airportSet = new EntitySet(
            name: 'Airports',
            entityType: $airportType,
            navigationPropertyBindings: [
                new NavigationPropertyBinding('flights', 'Flights'),
            ],
        );

        // Functions
        $countFunc = new EdmFunction(
            name: 'GetFlightCount',
            returnType: $int32,
        );
        $countImport = new FunctionImport('GetFlightCount', $countFunc);

        $byOriginFunc = new EdmFunction(
            name: 'GetFlightsByOrigin',
            returnType: $int32,
            parameters: [new FunctionParameter('origin', $string)],
        );
        $byOriginImport = new FunctionImport('GetFlightsByOrigin', $byOriginFunc);

        // Singleton: DefaultFlight
        $defaultFlightSingleton = new Singleton('DefaultFlight', $flightType);

        return $builder
            ->namespace('Test.Ns')
            ->addEntityType($flightType)
            ->addEntityType($passengerType)
            ->addEntityType($airportType)
            ->addEntitySet($flightSet)
            ->addEntitySet($passengerSet)
            ->addEntitySet($airportSet)
            ->addSingleton($defaultFlightSingleton)
            ->addFunction($countFunc)
            ->addFunction($byOriginFunc)
            ->addFunctionImport($countImport)
            ->addFunctionImport($byOriginImport);
    }

    protected function registerBindings(ResolverMapBuilder $map): void
    {
        $container = $map->getEdmx()->getEntityContainer();

        $map->eloquent($container->getEntitySet('Flights'), Flight::class);
        $map->eloquent($container->getEntitySet('Passengers'), Passenger::class);
        $map->eloquent($container->getEntitySet('Airports'), Airport::class);
    }

    protected function bindFunctions(RuntimeSchemaBuilderInterface $builder): void
    {
        $container = $builder->getEdmx()->getEntityContainer();

        $builder->bindFunctionImport(
            $container->getFunctionImport('GetFlightCount'),
            new class implements FunctionResolverInterface {
                public function resolve(QueryPlanInterface $plan): mixed
                {
                    return Flight::count();
                }
            },
        );

        $builder->bindFunctionImport(
            $container->getFunctionImport('GetFlightsByOrigin'),
            new class implements FunctionResolverInterface {
                public function resolve(QueryPlanInterface $plan): mixed
                {
                    /** @var FunctionInvocationPlan $plan */
                    $origin = $plan->parameters['origin']->value ?? null;
                    return Flight::where('origin', $origin)->count();
                }
            },
        );

        $builder->bindSingleton(
            $container->getSingleton('DefaultFlight'),
            new class implements SingletonResolverInterface {
                public function resolve(): array
                {
                    return ['id' => 0, 'origin' => 'default', 'destination' => 'default'];
                }
            },
        );
    }
}
