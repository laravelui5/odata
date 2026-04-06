<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeEnum;
use LaravelUi5\OData\Service\AbstractEntitySet;

/**
 * Test fixture: SQL-backed custom entity set using AbstractEntitySet.
 *
 * Aggregates flights by origin, producing a summary with passenger count.
 * Demonstrates the declarative columns()+key()+query() pattern — no manual
 * EDM construction, no Property/EntityType imports.
 */
final readonly class FlightSummaries extends AbstractEntitySet
{
    public function entitySetName(): string
    {
        return 'FlightSummaries';
    }

    public function key(): array
    {
        return ['origin'];
    }

    public function columns(): array
    {
        return [
            'origin'          => PrimitiveTypeEnum::String,
            'flight_count'    => PrimitiveTypeEnum::Int32,
            'passenger_count' => PrimitiveTypeEnum::Int32,
        ];
    }

    public function query(): Builder
    {
        return DB::query()->fromSub(
            DB::table('flights')
                ->leftJoin('passengers', 'flights.id', '=', 'passengers.flight_id')
                ->selectRaw('flights.origin')
                ->selectRaw('count(distinct flights.id) as flight_count')
                ->selectRaw('count(passengers.id) as passenger_count')
                ->groupBy('flights.origin'),
            't',
        );
    }
}
