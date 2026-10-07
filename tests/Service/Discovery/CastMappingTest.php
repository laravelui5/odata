<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Discovery\ModelDiscovery;
use LaravelUi5\OData\Tests\TestCase;

uses(TestCase::class);

/** The airports table: `construction_date` is a date, `sam_datetime` a datetime. */
class CastMappingAirport extends Model
{
    protected $table = 'airports';

    protected function casts(): array
    {
        return [
            'construction_date' => 'immutable_date',
            'sam_datetime'      => 'immutable_datetime',
        ];
    }
}

function castMappingType(string $column): EdmPrimitiveType
{
    $discovery = new ModelDiscovery();
    $discovery->add(CastMappingAirport::class);
    $builder = (new EdmBuilder())->namespace('Test.Ns');
    $discovery->apply($builder, 'Test.Ns');

    return $builder->build()->getSchema('Test.Ns')->getEntityTypes()[0]
        ->getProperty($column)->getType()->getPrimitiveType();
}

// OP17: an immutable_date is a pure date, like `date` — not a DateTimeOffset.
it('maps the immutable_date cast to Edm.Date', function () {
    expect(castMappingType('construction_date'))->toBe(EdmPrimitiveType::Date);
});

it('maps the immutable_datetime cast to Edm.DateTimeOffset', function () {
    expect(castMappingType('sam_datetime'))->toBe(EdmPrimitiveType::DateTimeOffset);
});
