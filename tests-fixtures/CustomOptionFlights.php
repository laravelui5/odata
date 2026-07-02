<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Http\CustomQueryOptions;
use LaravelUi5\OData\Service\AbstractEntitySet;

/**
 * Test fixture: an AbstractEntitySet that narrows its result by a **custom query
 * option** (`?origin=lhr`), read straight off the `CustomQueryOptions` passed to
 * query().
 *
 * Exercises the custom-query-option seam on both the direct and `$batch` request
 * paths — a bare list when no option is given, filtered to one origin when it is.
 */
final readonly class CustomOptionFlights extends AbstractEntitySet
{
    public function entitySetName(): string
    {
        return 'CustomOptionFlights';
    }

    public function key(): array
    {
        return ['id'];
    }

    public function columns(): array
    {
        return [
            'id'          => EdmPrimitiveType::Int32,
            'origin'      => EdmPrimitiveType::String,
            'destination' => EdmPrimitiveType::String,
        ];
    }

    public function query(CustomQueryOptions $options): Builder
    {
        $query  = DB::table('flights')->select('id', 'origin', 'destination');
        $origin = $options->get('origin');

        return $origin !== null ? $query->where('origin', $origin) : $query;
    }
}
