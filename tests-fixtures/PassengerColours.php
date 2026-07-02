<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Http\CustomQueryOptions;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Fixtures\Models\Enums\Colour;
use LaravelUi5\OData\Service\AbstractEntitySet;

/**
 * Test fixture: AbstractEntitySet exposing a backed-enum column.
 *
 * Drives the end-to-end EnumType integration test — `colour` declares
 * Colour::class so the engine projects it to `Edm.EnumType` in `$metadata`
 * and emits the symbolic member name in JSON output.
 */
final readonly class PassengerColours extends AbstractEntitySet
{
    public function entitySetName(): string
    {
        return 'PassengerColours';
    }

    public function key(): array
    {
        return ['id'];
    }

    public function columns(): array
    {
        return [
            'id'     => EdmPrimitiveType::Int64,
            'name'   => EdmPrimitiveType::String,
            'colour' => Colour::class,
        ];
    }

    public function query(CustomQueryOptions $options): Builder
    {
        return DB::table('passengers')->select('id', 'name', 'colour');
    }
}
