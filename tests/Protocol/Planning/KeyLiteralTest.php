<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Exception\BadRequestException;
use LaravelUi5\OData\Http\ODataRequest;
use LaravelUi5\OData\Protocol\Planning\QueryPlanner;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Contracts\EntitySetResolverInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;

/*
 * OP14: a key literal is validated against the key's type. A blind cast turned
 * `Products(abc)` into `Products(0)` — a valid query for the wrong key. Decimal,
 * temporal and Guid keys stay strings; string keys must be quoted.
 */

const KEY_TYPES = [
    'Ints'      => EdmPrimitiveType::Int32,
    'Bytes'     => EdmPrimitiveType::Byte,
    'Longs'     => EdmPrimitiveType::Int64,
    'Decimals'  => EdmPrimitiveType::Decimal,
    'Doubles'   => EdmPrimitiveType::Double,
    'Bools'     => EdmPrimitiveType::Boolean,
    'Guids'     => EdmPrimitiveType::Guid,
    'Strings'   => EdmPrimitiveType::String,
    'Dates'     => EdmPrimitiveType::Date,
    'Moments'   => EdmPrimitiveType::DateTimeOffset,
    'Times'     => EdmPrimitiveType::TimeOfDay,
    'Durations' => EdmPrimitiveType::Duration,
];

function keyLiteralSchema(): RuntimeSchemaInterface
{
    $builder = (new EdmBuilder())->namespace('Key.Ns');
    foreach (KEY_TYPES as $set => $type) {
        $key        = new Property('k', new PrimitiveType($type));
        $entityType = new EntityType(namespace: 'Key.Ns', name: rtrim($set, 's'), key: [$key], declaredProperties: [$key]);
        $builder->addEntityType($entityType)->addEntitySet(new EntitySet($set, $entityType));
    }

    $a = new Property('a', new PrimitiveType(EdmPrimitiveType::Int32));
    $b = new Property('b', new PrimitiveType(EdmPrimitiveType::String));
    $pair = new EntityType(namespace: 'Key.Ns', name: 'Pair', key: [$a, $b], declaredProperties: [$a, $b]);
    $builder->addEntityType($pair)->addEntitySet(new EntitySet('Pairs', $pair));

    $edmx = $builder->build();

    return new class ($edmx) implements RuntimeSchemaInterface {
        public function __construct(private EdmxInterface $edmx) {}
        public function getEdmx(): EdmxInterface { return $this->edmx; }
        public function getResolver(EntitySetInterface $set): EntitySetResolverInterface { throw new LogicException(); }
        public function getFunctionResolver(\LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface $import): \LaravelUi5\OData\Service\Contracts\FunctionResolverInterface { throw new LogicException(); }
        public function getSingletonResolver(\LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface $singleton): \LaravelUi5\OData\Service\Contracts\SingletonResolverInterface { throw new LogicException(); }
    };
}

/** @return array<string, mixed> key name → parsed value */
function keyOf(string $path): array
{
    $plan = (new QueryPlanner())->plan(new ODataRequest($path), keyLiteralSchema());

    return array_map(fn ($literal) => $literal->value, $plan->key->values);
}

function keyError(string $path): ?string
{
    try {
        keyOf($path);
    } catch (BadRequestException $e) {
        return $e->toError()['code'] ?? 'bad_request';
    }
    return null;
}

it('accepts and types valid literals', function () {
    expect(keyOf('/Ints(42)'))->toBe(['k' => 42])
        ->and(keyOf('/Ints(-7)'))->toBe(['k' => -7])
        ->and(keyOf('/Longs(9007199254740993)'))->toBe(['k' => 9007199254740993])
        ->and(keyOf('/Decimals(1234.500000)'))->toBe(['k' => '1234.500000'])   // exact, as a string
        ->and(keyOf('/Doubles(1.5)'))->toBe(['k' => 1.5])
        ->and(keyOf('/Bools(true)'))->toBe(['k' => true])
        ->and(keyOf('/Guids(0b2f6c1e-1d2a-4b3c-9d4e-5f6a7b8c9d0e)'))->toBe(['k' => '0b2f6c1e-1d2a-4b3c-9d4e-5f6a7b8c9d0e'])
        ->and(keyOf("/Strings('A')"))->toBe(['k' => 'A'])
        ->and(keyOf("/Strings('O''Brien')"))->toBe(['k' => "O'Brien"])
        ->and(keyOf('/Dates(2026-10-07)'))->toBe(['k' => '2026-10-07'])
        ->and(keyOf('/Moments(2026-10-07T12:30:00Z)'))->toBe(['k' => '2026-10-07T12:30:00Z'])
        ->and(keyOf('/Times(09:15:00)'))->toBe(['k' => '09:15:00'])
        ->and(keyOf('/Durations(P1DT2H)'))->toBe(['k' => 'P1DT2H']);
});

it('refuses a literal that is not of the key type', function (string $path) {
    expect(keyError($path))->toBe('invalid_key');
})->with([
    'int: letters'            => ['/Ints(abc)'],
    'int: fraction'           => ['/Ints(1.5)'],
    'int: out of range'       => ['/Ints(2147483648)'],
    'byte: negative'          => ['/Bytes(-1)'],
    'decimal: letters'        => ['/Decimals(1e5)'],
    'double: letters'         => ['/Doubles(abc)'],
    'bool: not true/false'    => ['/Bools(1)'],
    'guid: malformed'         => ['/Guids(1234)'],
    'string: unquoted'        => ['/Strings(A)'],
    'string: lone quote'      => ["/Strings('O'Brien')"],
    'date: wrong shape'       => ['/Dates(07.10.2026)'],
    'datetime: no offset'     => ['/Moments(2026-10-07T12:30:00)'],
    'time: wrong shape'       => ['/Times(9am)'],
    'duration: not ISO 8601'  => ['/Durations(2h)'],
]);

it('requires every part of a composite key exactly once', function () {
    expect(keyOf("/Pairs(a=1,b='x')"))->toBe(['a' => 1, 'b' => 'x'])
        ->and(keyError('/Pairs(a=1)'))->toBe('invalid_key')
        ->and(keyError("/Pairs(a=1,a=2,b='x')"))->toBe('invalid_key');
});
