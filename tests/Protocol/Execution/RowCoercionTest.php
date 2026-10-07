<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Container\EnumMember;
use LaravelUi5\OData\Edm\Container\EnumType;
use LaravelUi5\OData\Edm\Contracts\Type\TypeInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Protocol\Execution\RowCoercion;

function buildEntityType(array $columns): EntityType
{
    $properties = [];
    foreach ($columns as $name => $type) {
        $resolved    = $type instanceof EdmPrimitiveType ? new PrimitiveType($type) : $type;
        $properties[] = new Property($name, $resolved);
    }

    return new EntityType(
        namespace: 'Test.Ns',
        name: 'Row',
        key: [$properties[0]],
        declaredProperties: $properties,
    );
}

function tierEnum(): EnumType
{
    return new EnumType(
        namespace: 'Test.Ns',
        name: 'LicenseTier',
        members: [
            new EnumMember('Single',   1),
            new EnumMember('Platform', 2),
        ],
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

        it('coerces each column to its declared type in mixed schemas', function () {
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
            // A driver's decimal string becomes the JSON number $metadata promises (OP02).
            expect($row['amount'])->toBe(3.14);
            expect($row['created_at'])->toMatch('/^2026-05-05T12:34:56[+\-]\d{2}:\d{2}$/');
        });
    });

    describe('declared type over driver scalar (OP02)', function () {
        it('turns driver flags into booleans', function () {
            $type = buildEntityType(['id' => EdmPrimitiveType::Int32, 'flag' => EdmPrimitiveType::Boolean]);
            $c    = new RowCoercion($type);

            expect($c->apply(['flag' => 1])['flag'])->toBeTrue()
                ->and($c->apply(['flag' => 0])['flag'])->toBeFalse()
                ->and($c->apply(['flag' => '1'])['flag'])->toBeTrue()
                ->and($c->apply(['flag' => '0'])['flag'])->toBeFalse()
                ->and($c->apply(['flag' => 't'])['flag'])->toBeTrue()
                ->and($c->apply(['flag' => 'f'])['flag'])->toBeFalse()
                ->and($c->apply(['flag' => true])['flag'])->toBeTrue()
                ->and($c->apply(['flag' => null])['flag'])->toBeNull()
                ->and($c->apply(['flag' => 'maybe'])['flag'])->toBe('maybe');   // drift stays visible
        });

        it('turns driver strings into integers and numbers', function () {
            $type = buildEntityType([
                'id'     => EdmPrimitiveType::Int32,
                'small'  => EdmPrimitiveType::Int16,
                'big'    => EdmPrimitiveType::Int64,
                'amount' => EdmPrimitiveType::Decimal,
                'ratio'  => EdmPrimitiveType::Double,
            ]);
            $row = (new RowCoercion($type))->apply([
                'id' => '7', 'small' => 3.0, 'big' => '9007199254740993', 'amount' => '1234.500000', 'ratio' => '0.25',
            ]);

            expect($row)->toBe(['id' => 7, 'small' => 3, 'big' => 9007199254740993, 'amount' => 1234.5, 'ratio' => 0.25]);
        });

        it('keeps a value it cannot read as the declared type', function () {
            $type = buildEntityType(['id' => EdmPrimitiveType::Int32, 'amount' => EdmPrimitiveType::Decimal]);
            $row  = (new RowCoercion($type))->apply(['id' => 'abc', 'amount' => 'n/a']);

            expect($row)->toBe(['id' => 'abc', 'amount' => 'n/a']);
        });

        it('keeps Int64 and Decimal as exact strings under IEEE754Compatible', function () {
            $type = buildEntityType(['big' => EdmPrimitiveType::Int64, 'amount' => EdmPrimitiveType::Decimal]);
            $row  = (new RowCoercion($type, new LaravelUi5\OData\Protocol\Execution\WireFormat(true)))
                ->apply(['big' => 9007199254740993, 'amount' => '1234.500000']);

            expect($row)->toBe(['big' => '9007199254740993', 'amount' => '1234.500000']);
        });
    });

    describe('Edm.EnumType', function () {
        it('projects an int backing value to the symbolic member name', function () {
            $type = buildEntityType([
                'id'   => EdmPrimitiveType::Int64,
                'tier' => tierEnum(),
            ]);

            $row = (new RowCoercion($type))->apply([
                'id'   => 1,
                'tier' => 1,
            ]);

            expect($row['tier'])->toBe('Single');
        });

        it('falls through to the int when the value is not a known member', function () {
            $type = buildEntityType([
                'id'   => EdmPrimitiveType::Int64,
                'tier' => tierEnum(),
            ]);

            $row = (new RowCoercion($type))->apply([
                'id'   => 1,
                'tier' => 99,
            ]);

            expect($row['tier'])->toBe(99);
        });

        it('preserves null on a nullable enum column', function () {
            $type = buildEntityType([
                'id'   => EdmPrimitiveType::Int64,
                'tier' => tierEnum(),
            ]);

            $row = (new RowCoercion($type))->apply([
                'id'   => 1,
                'tier' => null,
            ]);

            expect($row['tier'])->toBeNull();
        });

        it('coerces alongside other types in a mixed schema', function () {
            $type = buildEntityType([
                'id'         => EdmPrimitiveType::Int64,
                'name'       => EdmPrimitiveType::String,
                'tier'       => tierEnum(),
                'created_at' => EdmPrimitiveType::DateTimeOffset,
            ]);

            $row = (new RowCoercion($type))->apply([
                'id'         => 7,
                'name'       => 'alice',
                'tier'       => 2,
                'created_at' => '2026-05-05 12:34:56',
            ]);

            expect($row['id'])->toBe(7);
            expect($row['name'])->toBe('alice');
            expect($row['tier'])->toBe('Platform');
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
