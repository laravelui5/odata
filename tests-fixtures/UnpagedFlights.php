<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Http\CustomQueryOptions;
use LaravelUi5\OData\Service\AbstractEntitySet;
use LaravelUi5\OData\Service\Contracts\UnpagedEntitySetInterface;

/** Test fixture: A set the server never pages (ROADMAP OP48) — every row in one response. */
final readonly class UnpagedFlights extends AbstractEntitySet implements UnpagedEntitySetInterface
{
    public function entitySetName(): string
    {
        return 'UnpagedFlights';
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
