<?php

declare(strict_types=1);

use Illuminate\Database\Query\Builder;
use LaravelUi5\OData\Edm\Contracts\ColumnarSchemaInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Service\Contracts\EntitySetSourceInterface;
use LaravelUi5\OData\Service\Contracts\SqlQueryInterface;

describe('SqlQueryInterface', function () {

    it('extends ColumnarSchemaInterface', function () {
        expect(is_subclass_of(SqlQueryInterface::class, ColumnarSchemaInterface::class))->toBeTrue();
    });

    it('extends EntitySetSourceInterface', function () {
        expect(is_subclass_of(SqlQueryInterface::class, EntitySetSourceInterface::class))->toBeTrue();
    });

    it('can be implemented with columns, key, and query', function () {
        $impl = new class implements SqlQueryInterface {
            public function columns(): array
            {
                return [
                    'id'   => EdmPrimitiveType::Int64,
                    'name' => EdmPrimitiveType::String,
                ];
            }

            public function key(): array
            {
                return ['id'];
            }

            public function query(\LaravelUi5\OData\Http\CustomQueryOptions $options): Builder
            {
                return \Illuminate\Support\Facades\DB::table('test');
            }
        };

        expect($impl)->toBeInstanceOf(SqlQueryInterface::class)
            ->and($impl)->toBeInstanceOf(ColumnarSchemaInterface::class)
            ->and($impl)->toBeInstanceOf(EntitySetSourceInterface::class)
            ->and($impl->columns())->toHaveCount(2)
            ->and($impl->key())->toBe(['id']);
    });
});
