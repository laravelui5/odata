<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Protocol\Execution\RowCoercion;

function buildEntityType(array $columns): EntityType
{
    $properties = [];
    foreach ($columns as $name => $type) {
        $properties[] = new Property($name, new PrimitiveType($type));
    }

    return new EntityType(
        namespace: 'Test.Ns',
        name: 'Row',
        key: [$properties[0]],
        declaredProperties: $properties,
    );
}

describe('RowCoercion', function () {

    describe('Edm.DateTimeOffset', function () {
        it('coerces a MySQL datetime string to RFC 3339', function () {
            $type = buildEntityType([
                'id'         => EdmPrimitiveType::Int64,
                'created_at' => EdmPrimitiveType::DateTimeOffset,
            ]);

            $row = (new RowCoercion($type))->apply([
                'id'         => 1,
                'created_at' => '2026-05-05 12:34:56',
            ]);

            expect($row['created_at'])->toMatch('/^2026-05-05T12:34:56[+\-]\d{2}:\d{2}$/');
            expect($row['id'])->toBe(1);
        });

        it('round-trips an already-correct RFC 3339 string', function () {
            $type = buildEntityType([
                'id'         => EdmPrimitiveType::Int64,
                'created_at' => EdmPrimitiveType::DateTimeOffset,
            ]);

            $row = (new RowCoercion($type))->apply([
                'id'         => 1,
                'created_at' => '2026-05-05T12:34:56Z',
            ]);

            expect($row['created_at'])->toMatch('/^2026-05-05T12:34:56[+\-]\d{2}:\d{2}$/');
        });

        it('preserves null values on nullable columns', function () {
            $type = buildEntityType([
                'id'         => EdmPrimitiveType::Int64,
                'closed_at'  => EdmPrimitiveType::DateTimeOffset,
            ]);

            $row = (new RowCoercion($type))->apply([
                'id'        => 1,
                'closed_at' => null,
            ]);

            expect($row['closed_at'])->toBeNull();
        });
    });

    describe('Edm.Date', function () {
        it('coerces a date-time string to Y-m-d', function () {
            $type = buildEntityType([
                'id'      => EdmPrimitiveType::Int64,
                'birthed' => EdmPrimitiveType::Date,
            ]);

            $row = (new RowCoercion($type))->apply([
                'id'      => 1,
                'birthed' => '2026-05-05 12:34:56',
            ]);

            expect($row['birthed'])->toBe('2026-05-05');
        });

        it('passes through an already-correct Y-m-d string', function () {
            $type = buildEntityType([
                'id'      => EdmPrimitiveType::Int64,
                'birthed' => EdmPrimitiveType::Date,
            ]);

            $row = (new RowCoercion($type))->apply([
                'id'      => 1,
                'birthed' => '2026-05-05',
            ]);

            expect($row['birthed'])->toBe('2026-05-05');
        });
    });

    describe('Edm.TimeOfDay', function () {
        it('coerces a date-time string to H:i:s', function () {
            $type = buildEntityType([
                'id'      => EdmPrimitiveType::Int64,
                'opens_at'=> EdmPrimitiveType::TimeOfDay,
            ]);

            $row = (new RowCoercion($type))->apply([
                'id'       => 1,
                'opens_at' => '2026-05-05 09:00:00',
            ]);

            expect($row['opens_at'])->toBe('09:00:00');
        });

        it('passes through an already-correct H:i:s string', function () {
            $type = buildEntityType([
                'id'      => EdmPrimitiveType::Int64,
                'opens_at'=> EdmPrimitiveType::TimeOfDay,
            ]);

            $row = (new RowCoercion($type))->apply([
                'id'       => 1,
                'opens_at' => '09:00:00',
            ]);

            expect($row['opens_at'])->toBe('09:00:00');
        });
    });

    describe('non-temporal columns', function () {
        it('is a no-op for entity types without temporal properties', function () {
            $type = buildEntityType([
                'id'   => EdmPrimitiveType::Int64,
                'name' => EdmPrimitiveType::String,
            ]);

            $input = ['id' => 1, 'name' => 'alice'];
            $row   = (new RowCoercion($type))->apply($input);

            expect($row)->toBe($input);
        });

        it('leaves non-temporal columns untouched in mixed schemas', function () {
            $type = buildEntityType([
                'id'         => EdmPrimitiveType::Int64,
                'name'       => EdmPrimitiveType::String,
                'amount'     => EdmPrimitiveType::Decimal,
                'created_at' => EdmPrimitiveType::DateTimeOffset,
            ]);

            $row = (new RowCoercion($type))->apply([
                'id'         => 7,
                'name'       => 'alice',
                'amount'     => '3.14',
                'created_at' => '2026-05-05 12:34:56',
            ]);

            expect($row['id'])->toBe(7);
            expect($row['name'])->toBe('alice');
            expect($row['amount'])->toBe('3.14');
            expect($row['created_at'])->toMatch('/^2026-05-05T12:34:56[+\-]\d{2}:\d{2}$/');
        });
    });

    describe('partial selection', function () {
        it('skips coercion for columns not present in the row', function () {
            $type = buildEntityType([
                'id'         => EdmPrimitiveType::Int64,
                'created_at' => EdmPrimitiveType::DateTimeOffset,
            ]);

            $row = (new RowCoercion($type))->apply(['id' => 1]);

            expect($row)->toBe(['id' => 1]);
        });
    });
});
