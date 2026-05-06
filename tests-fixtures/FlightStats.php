<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Protocol\Planning\EntitySetQueryPlan;
use LaravelUi5\OData\Protocol\Planning\ExpandItem;
use LaravelUi5\OData\Service\Contracts\CustomEntitySetInterface;
use LaravelUi5\OData\Service\Contracts\QueryPlanInterface;
use LaravelUi5\OData\Service\Contracts\VirtualExpandResolverInterface;

/**
 * Test fixture: virtual expand that computes stats for a Flight.
 *
 * Usage: Flights(1)?$expand=stats
 * Returns: [{ stat_id: 1, metric: 'passenger_count', value: 2 }]
 */
final class FlightStats implements CustomEntitySetInterface, VirtualExpandResolverInterface
{
    public function entitySetName(): string
    {
        return 'FlightStats';
    }

    public function entityType(string $namespace): EntityTypeInterface
    {
        $int32  = new PrimitiveType(EdmPrimitiveType::Int32);
        $string = new PrimitiveType(EdmPrimitiveType::String);

        $keyProp = new Property('stat_id', $int32);

        return new EntityType(
            namespace: $namespace,
            name: 'FlightStat',
            key: [$keyProp],
            declaredProperties: [
                $keyProp,
                new Property('metric', $string),
                new Property('value', $int32),
            ],
        );
    }

    public function expandsOn(): array
    {
        return ['Flight' => 'stats'];
    }

    public function resolveExpand(array $parentRow, string $parentEntityType, ExpandItem $expand): array
    {
        $flightId = $parentRow['id'] ?? null;

        if ($flightId === null) {
            return [];
        }

        $count = Models\Passenger::where('flight_id', $flightId)->count();

        return [
            ['stat_id' => 1, 'metric' => 'passenger_count', 'value' => $count],
        ];
    }

    public function resolve(QueryPlanInterface $plan): \Generator
    {
        yield from [];
    }

    public function count(QueryPlanInterface $plan): int
    {
        return 0;
    }
}
