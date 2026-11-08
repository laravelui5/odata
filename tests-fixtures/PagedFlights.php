<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Http\CustomQueryOptions;
use LaravelUi5\OData\Service\AbstractEntitySet;

/** Test fixture: An ordinary set over the same table — paged by the server as configured. */
final readonly class PagedFlights extends AbstractEntitySet
{
    public function entitySetName(): string
    {
        return 'PagedFlights';
    }

    public function key(): array
    {
        return ['id'];
    }

    public function columns(): array
    {
        return [
            'id'     => EdmPrimitiveType::Int32,
            'origin' => EdmPrimitiveType::String,
        ];
    }

    public function query(CustomQueryOptions $options): Builder
    {
        return DB::table('flights')->select('id', 'origin');
    }
}
