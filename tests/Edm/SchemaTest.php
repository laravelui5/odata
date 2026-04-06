<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Container\EnumType;
use LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeEnum;
use LaravelUi5\OData\Edm\EdmFunction;
use LaravelUi5\OData\Edm\Schema;
use LaravelUi5\OData\Edm\Type\ComplexType;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\TypeDefinition;

// ── Tests ──────────────────────────────────────────────────────────────────────

describe('Schema', function () {

    describe('identity', function () {
        it('returns the namespace', function () {
            $schema = new Schema('My.Service');
            expect($schema->getNamespace())->toBe('My.Service');
        });

        it('returns null alias when none is set', function () {
            $schema = new Schema('My.Service');
            expect($schema->getAlias())->toBeNull();
        });

        it('returns the alias when provided', function () {
            $schema = new Schema('My.Service', alias: 'MyAlias');
            expect($schema->getAlias())->toBe('MyAlias');
        });
    });

    describe('getEntityType', function () {
        it('returns null when no entity types are registered', function () {
            $schema = new Schema('My.Service');
            expect($schema->getEntityType('Customer'))->toBeNull();
        });

        it('returns the matching entity type by name', function () {
            $type   = new EntityType(namespace: 'My.Service', name: 'Customer');
            $schema = new Schema('My.Service', entityTypes: [$type]);

            expect($schema->getEntityType('Customer'))->toBe($type);
        });

        it('returns null for an unknown entity type name', function () {
            $type   = new EntityType(namespace: 'My.Service', name: 'Customer');
            $schema = new Schema('My.Service', entityTypes: [$type]);

            expect($schema->getEntityType('Order'))->toBeNull();
        });

        it('returns all entity types via getEntityTypes()', function () {
            $a = new EntityType(namespace: 'My.Service', name: 'A');
            $b = new EntityType(namespace: 'My.Service', name: 'B');
            $schema = new Schema('My.Service', entityTypes: [$a, $b]);

            expect($schema->getEntityTypes())->toHaveCount(2);
        });
    });

    describe('getComplexType', function () {
        it('returns null when no complex types are registered', function () {
            $schema = new Schema('My.Service');
            expect($schema->getComplexType('Address'))->toBeNull();
        });

        it('returns the matching complex type by name', function () {
            $type   = new ComplexType(namespace: 'My.Service', name: 'Address');
            $schema = new Schema('My.Service', complexTypes: [$type]);

            expect($schema->getComplexType('Address'))->toBe($type);
        });

        it('returns null for an unknown complex type name', function () {
            $type   = new ComplexType(namespace: 'My.Service', name: 'Address');
            $schema = new Schema('My.Service', complexTypes: [$type]);

            expect($schema->getComplexType('GeoPoint'))->toBeNull();
        });
    });

    describe('getEnumType', function () {
        it('returns null when no enum types are registered', function () {
            $schema = new Schema('My.Service');
            expect($schema->getEnumType('Color'))->toBeNull();
        });

        it('returns the matching enum type by name', function () {
            $type   = new EnumType(namespace: 'My.Service', name: 'Color');
            $schema = new Schema('My.Service', enumTypes: [$type]);

            expect($schema->getEnumType('Color'))->toBe($type);
        });

        it('returns null for an unknown enum type name', function () {
            $type   = new EnumType(namespace: 'My.Service', name: 'Color');
            $schema = new Schema('My.Service', enumTypes: [$type]);

            expect($schema->getEnumType('Status'))->toBeNull();
        });
    });

    describe('getTypeDefinition', function () {
        it('returns null when no type definitions are registered', function () {
            $schema = new Schema('My.Service');
            expect($schema->getTypeDefinition('Weight'))->toBeNull();
        });

        it('returns the matching type definition by name', function () {
            $def    = new TypeDefinition(namespace: 'My.Service', name: 'Weight', underlyingType: PrimitiveTypeEnum::Decimal);
            $schema = new Schema('My.Service', typeDefinitions: [$def]);

            expect($schema->getTypeDefinition('Weight'))->toBe($def);
        });

        it('returns null for an unknown type definition name', function () {
            $def    = new TypeDefinition(namespace: 'My.Service', name: 'Weight', underlyingType: PrimitiveTypeEnum::Decimal);
            $schema = new Schema('My.Service', typeDefinitions: [$def]);

            expect($schema->getTypeDefinition('Distance'))->toBeNull();
        });
    });

    describe('getFunctions — grouping', function () {
        it('returns an empty array when no functions are registered', function () {
            $schema = new Schema('My.Service');
            expect($schema->getFunctions())->toBe([]);
        });

        it('groups overloads under the same name key', function () {
            $f1 = new EdmFunction('Search');
            $f2 = new EdmFunction('Search');
            $schema = new Schema('My.Service', functions: [$f1, $f2]);

            $grouped = $schema->getFunctions();

            expect($grouped)->toHaveKey('Search');
            expect($grouped['Search'])->toHaveCount(2);
        });

        it('keeps distinct names in separate groups', function () {
            $search  = new EdmFunction('Search');
            $convert = new EdmFunction('Convert');
            $schema  = new Schema('My.Service', functions: [$search, $convert]);

            $grouped = $schema->getFunctions();

            expect($grouped)->toHaveKey('Search');
            expect($grouped)->toHaveKey('Convert');
            expect($grouped['Search'])->toHaveCount(1);
            expect($grouped['Convert'])->toHaveCount(1);
        });
    });

    describe('getFunction', function () {
        it('returns all overloads for the given name', function () {
            $f1 = new EdmFunction('Search');
            $f2 = new EdmFunction('Search');
            $f3 = new EdmFunction('Convert');
            $schema = new Schema('My.Service', functions: [$f1, $f2, $f3]);

            expect($schema->getFunction('Search'))->toHaveCount(2);
        });

        it('returns an empty array when the function name is unknown', function () {
            $schema = new Schema('My.Service', functions: [new EdmFunction('Search')]);

            expect($schema->getFunction('Unknown'))->toBe([]);
        });
    });
});
