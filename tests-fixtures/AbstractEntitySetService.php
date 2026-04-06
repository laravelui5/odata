<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\Container\NavigationPropertyBinding;
use LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeEnum;
use LaravelUi5\OData\Edm\Property\NavigationProperty;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Builder\ResolverMapBuilder;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Fixtures\Models\Passenger;

/**
 * Test fixture for AbstractEntitySet HTTP round-trip tests.
 *
 * Registers FlightSummaries (AbstractEntitySet) alongside manually defined
 * Flights and Passengers entity sets that provide the underlying data.
 */
final class AbstractEntitySetService extends ODataService
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
        $this->discoverCustomEntitySet(FlightSummaries::class);

        $int32  = new PrimitiveType(PrimitiveTypeEnum::Int32);
        $string = new PrimitiveType(PrimitiveTypeEnum::String);

        // Minimal Flight + Passenger types for the underlying data
        $flightId   = new Property('id', $int32);
        $originProp = new Property('origin', $string);
        $destProp   = new Property('destination', $string);

        $passengerId       = new Property('id', $int32);
        $passengerName     = new Property('name', $string);
        $passengerFlightId = new Property('flight_id', $int32);

        $passengerType = new EntityType(
            namespace: 'Test.Ns',
            name: 'Passenger',
            key: [$passengerId],
            declaredProperties: [$passengerId, $passengerName, $passengerFlightId],
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

        return $builder
            ->namespace('Test.Ns')
            ->addEntityType($flightType)
            ->addEntityType($passengerType)
            ->addEntitySet($flightSet)
            ->addEntitySet($passengerSet);
    }

    protected function registerBindings(ResolverMapBuilder $map): void
    {
        $container = $map->getEdmx()->getEntityContainer();

        $map->eloquent($container->getEntitySet('Flights'), Flight::class);
        $map->eloquent($container->getEntitySet('Passengers'), Passenger::class);
    }
}
