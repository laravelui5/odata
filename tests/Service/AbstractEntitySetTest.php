<?php

declare(strict_types=1);

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Edm\Contracts\ColumnarSchemaInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Service\AbstractEntitySet;
use LaravelUi5\OData\Service\Contracts\CustomEntitySetInterface;
use LaravelUi5\OData\Service\Contracts\EntitySetSourceInterface;
use LaravelUi5\OData\Service\Contracts\SqlQueryInterface;

// ── Helpers ──────────────────────────────────────────────────────────────────

function makeEntitySet(string $name, array $columns, ?array $key = null): AbstractEntitySet
{
    return new readonly class($name, $columns, $key) extends AbstractEntitySet {
        public function __construct(
            private string $name,
            private array $cols,
            private ?array $k,
        ) {
            parent::__construct();
        }

        public function entitySetName(): string
        {
            return $this->name;
        }

        public function columns(): array
        {
            return $this->cols;
        }

        public function key(): array
        {
            return $this->k ?? parent::key();
        }

        public function query(): Builder
        {
            return DB::table('dummy');
        }
    };
}

// ── Tests ────────────────────────────────────────────────────────────────────

describe('AbstractEntitySet', function () {

    describe('contract', function () {
        it('implements CustomEntitySetInterface', function () {
            $set = makeEntitySet('Items', ['id' => EdmPrimitiveType::Int64]);
            expect($set)->toBeInstanceOf(CustomEntitySetInterface::class);
        });

        it('implements ColumnarSchemaInterface', function () {
            $set = makeEntitySet('Items', ['id' => EdmPrimitiveType::Int64]);
            expect($set)->toBeInstanceOf(ColumnarSchemaInterface::class);
        });

        it('implements SqlQueryInterface', function () {
            $set = makeEntitySet('Items', ['id' => EdmPrimitiveType::Int64]);
            expect($set)->toBeInstanceOf(SqlQueryInterface::class);
        });

        it('implements EntitySetSourceInterface', function () {
            $set = makeEntitySet('Items', ['id' => EdmPrimitiveType::Int64]);
            expect($set)->toBeInstanceOf(EntitySetSourceInterface::class);
        });
    });

    describe('entityType() assembly', function () {
        it('builds an EntityType from columns and key', function () {
            $set = makeEntitySet('BillableProjects', [
                'project_id'   => EdmPrimitiveType::Int64,
                'customer'     => EdmPrimitiveType::String,
                'hours_posted' => EdmPrimitiveType::Double,
            ], key: ['project_id']);

            $type = $set->entityType('io.pragmatiqu');

            expect($type)->toBeInstanceOf(EntityTypeInterface::class)
                ->and($type->getName())->toBe('BillableProject')
                ->and($type->getQualifiedName())->toBe('io.pragmatiqu.BillableProject');
        });

        it('declares all columns as properties', function () {
            $set = makeEntitySet('Orders', [
                'id'     => EdmPrimitiveType::Int64,
                'total'  => EdmPrimitiveType::Decimal,
                'status' => EdmPrimitiveType::String,
            ], key: ['id']);

            $type = $set->entityType('Test.Ns');
            $propNames = array_map(fn ($p) => $p->getName(), $type->getDeclaredProperties());

            expect($propNames)->toBe(['id', 'total', 'status']);
        });

        it('maps EdmPrimitiveType cases to correct property types', function () {
            $set = makeEntitySet('Items', [
                'id'      => EdmPrimitiveType::Int64,
                'name'    => EdmPrimitiveType::String,
                'price'   => EdmPrimitiveType::Decimal,
                'active'  => EdmPrimitiveType::Boolean,
                'created' => EdmPrimitiveType::DateTimeOffset,
            ], key: ['id']);

            $type = $set->entityType('Test.Ns');
            $props = $type->getDeclaredProperties();

            $typeMap = [];
            foreach ($props as $prop) {
                $typeMap[$prop->getName()] = $prop->getType()->getQualifiedName();
            }

            expect($typeMap)->toBe([
                'id'      => 'Edm.Int64',
                'name'    => 'Edm.String',
                'price'   => 'Edm.Decimal',
                'active'  => 'Edm.Boolean',
                'created' => 'Edm.DateTimeOffset',
            ]);
        });

        it('sets the correct key properties', function () {
            $set = makeEntitySet('Orders', [
                'id'     => EdmPrimitiveType::Int64,
                'total'  => EdmPrimitiveType::Decimal,
            ], key: ['id']);

            $type = $set->entityType('Test.Ns');
            $keyNames = array_map(fn ($p) => $p->getName(), $type->getKey());

            expect($keyNames)->toBe(['id']);
        });
    });

    describe('key() defaults', function () {
        it('defaults key to first column when not overridden', function () {
            $set = makeEntitySet('Items', [
                'item_code' => EdmPrimitiveType::String,
                'name'      => EdmPrimitiveType::String,
            ]);

            expect($set->key())->toBe(['item_code']);
        });
    });

    describe('composite keys', function () {
        it('supports composite keys', function () {
            $set = makeEntitySet('TenantProjects', [
                'tenant_id'  => EdmPrimitiveType::Int64,
                'project_id' => EdmPrimitiveType::Int64,
                'name'       => EdmPrimitiveType::String,
            ], key: ['tenant_id', 'project_id']);

            $type = $set->entityType('Test.Ns');
            $keyNames = array_map(fn ($p) => $p->getName(), $type->getKey());

            expect($keyNames)->toBe(['tenant_id', 'project_id']);
        });

        it('preserves key order matching columns order', function () {
            $set = makeEntitySet('Items', [
                'alpha' => EdmPrimitiveType::String,
                'beta'  => EdmPrimitiveType::Int32,
                'gamma' => EdmPrimitiveType::Int64,
            ], key: ['gamma', 'alpha']);

            $type = $set->entityType('Test.Ns');
            $keyNames = array_map(fn ($p) => $p->getName(), $type->getKey());

            // Key props are resolved from the columns map, so order follows columns
            expect($keyNames)->toBe(['alpha', 'gamma']);
        });
    });

    describe('entity type name singularization', function () {
        it('singularizes the entity set name', function () {
            $cases = [
                'BillableProjects' => 'BillableProject',
                'FlightSummaries'  => 'FlightSummary',
                'Addresses'        => 'Address',
                'People'           => 'Person',
                'Statuses'         => 'Status',
            ];

            foreach ($cases as $setName => $expectedTypeName) {
                $set = makeEntitySet($setName, ['id' => EdmPrimitiveType::Int64]);
                $type = $set->entityType('Test.Ns');
                expect($type->getName())->toBe($expectedTypeName, "Failed for {$setName}");
            }
        });
    });

    describe('all primitive types', function () {
        it('supports every commonly used EdmPrimitiveType case', function () {
            $allTypes = [
                'binary_col'   => EdmPrimitiveType::Binary,
                'bool_col'     => EdmPrimitiveType::Boolean,
                'byte_col'     => EdmPrimitiveType::Byte,
                'date_col'     => EdmPrimitiveType::Date,
                'datetime_col' => EdmPrimitiveType::DateTimeOffset,
                'decimal_col'  => EdmPrimitiveType::Decimal,
                'double_col'   => EdmPrimitiveType::Double,
                'duration_col' => EdmPrimitiveType::Duration,
                'guid_col'     => EdmPrimitiveType::Guid,
                'int16_col'    => EdmPrimitiveType::Int16,
                'int32_col'    => EdmPrimitiveType::Int32,
                'int64_col'    => EdmPrimitiveType::Int64,
                'sbyte_col'    => EdmPrimitiveType::SByte,
                'single_col'   => EdmPrimitiveType::Single,
                'string_col'   => EdmPrimitiveType::String,
                'time_col'     => EdmPrimitiveType::TimeOfDay,
            ];

            $set = makeEntitySet('TypeTests', $allTypes, key: ['guid_col']);
            $type = $set->entityType('Test.Ns');

            expect($type->getDeclaredProperties())->toHaveCount(16);

            $props = $type->getDeclaredProperties();
            foreach ($props as $prop) {
                $colName = $prop->getName();
                $expectedEdm = $allTypes[$colName]->value;
                expect($prop->getType()->getQualifiedName())->toBe($expectedEdm, "Type mismatch for {$colName}");
            }
        });
    });

    describe('entityType() override', function () {
        it('allows subclasses to override entityType() for full control', function () {
            $set = new readonly class extends AbstractEntitySet {
                public function entitySetName(): string
                {
                    return 'CustomItems';
                }

                public function columns(): array
                {
                    return ['id' => EdmPrimitiveType::Int64];
                }

                public function query(): Builder
                {
                    return DB::table('dummy');
                }

                public function entityType(string $namespace): EntityTypeInterface
                {
                    // Completely custom — ignores columns()
                    $prop = new \LaravelUi5\OData\Edm\Property\Property(
                        'custom_id',
                        new \LaravelUi5\OData\Edm\Type\PrimitiveType(EdmPrimitiveType::Guid),
                    );

                    return new \LaravelUi5\OData\Edm\Type\EntityType(
                        namespace: $namespace,
                        name: 'CustomItem',
                        key: [$prop],
                        declaredProperties: [$prop],
                    );
                }
            };

            $type = $set->entityType('My.Ns');

            expect($type->getName())->toBe('CustomItem')
                ->and($type->getDeclaredProperties())->toHaveCount(1)
                ->and($type->getDeclaredProperties()[0]->getName())->toBe('custom_id');
        });
    });
});
