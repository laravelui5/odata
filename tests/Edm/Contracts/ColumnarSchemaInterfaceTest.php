<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Contracts\ColumnarSchemaInterface;
use LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeEnum;

/**
 * Tests for ColumnarSchemaInterface — the common denominator between
 * OData entity sets and Core artifact types (Report, AnalyticsSet, ValueHelp).
 */
describe('ColumnarSchemaInterface', function () {
    it('can be implemented as a plain class without framework dependencies', function () {
        $schema = new readonly class implements ColumnarSchemaInterface {
            public function columns(): array
            {
                return [
                    'project_id' => PrimitiveTypeEnum::Int64,
                    'name'       => PrimitiveTypeEnum::String,
                    'amount'     => PrimitiveTypeEnum::Decimal,
                ];
            }

            public function key(): array
            {
                return ['project_id'];
            }
        };

        expect($schema->columns())->toHaveCount(3)
            ->and($schema->columns()['project_id'])->toBe(PrimitiveTypeEnum::Int64)
            ->and($schema->columns()['name'])->toBe(PrimitiveTypeEnum::String)
            ->and($schema->columns()['amount'])->toBe(PrimitiveTypeEnum::Decimal)
            ->and($schema->key())->toBe(['project_id']);
    });

    it('supports composite keys', function () {
        $schema = new readonly class implements ColumnarSchemaInterface {
            public function columns(): array
            {
                return [
                    'tenant_id'  => PrimitiveTypeEnum::Int64,
                    'project_id' => PrimitiveTypeEnum::Int64,
                    'name'       => PrimitiveTypeEnum::String,
                ];
            }

            public function key(): array
            {
                return ['tenant_id', 'project_id'];
            }
        };

        expect($schema->key())->toBe(['tenant_id', 'project_id']);
    });

    it('has no framework dependencies (Edm-layer contract)', function () {
        $ref = new ReflectionClass(ColumnarSchemaInterface::class);

        // The interface lives in Edm\Contracts — zero external dependencies
        expect($ref->getNamespaceName())->toBe('LaravelUi5\OData\Edm\Contracts');
    });
});
