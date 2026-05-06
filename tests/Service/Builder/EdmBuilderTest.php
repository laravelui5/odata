<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\Container\EnumMember;
use LaravelUi5\OData\Edm\Container\EnumType;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Service\Builder\EdmBuilder;

// ── Tests ──────────────────────────────────────────────────────────────────────

describe('EdmBuilder', function () {

    describe('defaults', function () {
        it('produces an EdmxInterface on build()', function () {
            $edmx = (new EdmBuilder)->namespace('Test.Service')->build();
            expect($edmx)->toBeInstanceOf(EdmxInterface::class);
        });

        it('defaults version to 4.0', function () {
            $edmx = (new EdmBuilder)->namespace('Test.Service')->build();
            expect($edmx->getVersion())->toBe('4.0');
        });

        it('defaults container name to DefaultContainer', function () {
            $edmx = (new EdmBuilder)->namespace('Test.Service')->build();
            expect($edmx->getEntityContainer()->getName())->toBe('DefaultContainer');
        });

        it('has no references by default', function () {
            $edmx = (new EdmBuilder)->namespace('Test.Service')->build();
            expect($edmx->getReferences())->toBe([]);
        });
    });

    describe('identity configuration', function () {
        it('respects a custom version', function () {
            $edmx = (new EdmBuilder)->namespace('Test.Service')->version('4.01')->build();
            expect($edmx->getVersion())->toBe('4.01');
        });

        it('respects a custom container name', function () {
            $edmx = (new EdmBuilder)->namespace('Test.Service')->containerName('MyContainer')->build();
            expect($edmx->getEntityContainer()->getName())->toBe('MyContainer');
        });

        it('stores the namespace in the schema', function () {
            $edmx = (new EdmBuilder)->namespace('Partner.Service')->build();
            expect($edmx->getSchemas())->toHaveKey('Partner.Service');
        });

        it('stores the alias in the schema', function () {
            $edmx = (new EdmBuilder)->namespace('Partner.Service')->alias('PS')->build();
            $schema = $edmx->getSchemas()['Partner.Service'];
            expect($schema->getAlias())->toBe('PS');
        });
    });

    describe('entity types', function () {
        it('places added entity types in the schema', function () {
            $type  = new EntityType(namespace: 'Test.Service', name: 'Customer');
            $edmx  = (new EdmBuilder)
                ->namespace('Test.Service')
                ->addEntityType($type)
                ->build();

            $schema = $edmx->getSchemas()['Test.Service'];
            expect($schema->getEntityTypes())->toHaveCount(1);
            expect($schema->getEntityType('Customer'))->toBe($type);
        });
    });

    describe('entity sets', function () {
        it('places added entity sets in the container', function () {
            $entityType = new EntityType(namespace: 'Test.Service', name: 'Customer');
            $set = new EntitySet(name: 'Customers', entityType: $entityType);

            $edmx = (new EdmBuilder)
                ->namespace('Test.Service')
                ->addEntitySet($set)
                ->build();

            $container = $edmx->getEntityContainer();
            expect($container->getEntitySets())->toHaveCount(1);
            expect($container->getEntitySet('Customers'))->toBe($set);
        });
    });

    describe('enum types', function () {
        it('dedupes a structurally identical re-registration', function () {
            $first = new EnumType(
                namespace: 'Test.Service',
                name: 'LicenseTier',
                underlyingType: EdmPrimitiveType::Int32,
                members: [
                    new EnumMember('Single', 1),
                    new EnumMember('Platform', 2),
                ],
            );
            $second = new EnumType(
                namespace: 'Test.Service',
                name: 'LicenseTier',
                underlyingType: EdmPrimitiveType::Int32,
                members: [
                    new EnumMember('Single', 1),
                    new EnumMember('Platform', 2),
                ],
            );

            $edmx = (new EdmBuilder)
                ->namespace('Test.Service')
                ->addEnumType($first)
                ->addEnumType($second)
                ->build();

            $schema = $edmx->getSchemas()['Test.Service'];
            expect($schema->getEnumTypes())->toHaveCount(1);
            expect($schema->getEnumTypes()[0])->toBe($first);
        });

        it('keeps two distinct enum types side by side', function () {
            $tier = new EnumType(
                namespace: 'Test.Service',
                name: 'LicenseTier',
                members: [new EnumMember('Single', 1), new EnumMember('Platform', 2)],
            );
            $mode = new EnumType(
                namespace: 'Test.Service',
                name: 'RenewalMode',
                members: [new EnumMember('Auto', 1), new EnumMember('Prepay', 2)],
            );

            $edmx = (new EdmBuilder)
                ->namespace('Test.Service')
                ->addEnumType($tier)
                ->addEnumType($mode)
                ->build();

            $schema = $edmx->getSchemas()['Test.Service'];
            expect($schema->getEnumTypes())->toHaveCount(2);
        });

        it('throws LogicException on a same-name collision with a different definition', function () {
            $first = new EnumType(
                namespace: 'Test.Service',
                name: 'Status',
                members: [new EnumMember('Active', 1), new EnumMember('Inactive', 2)],
            );
            $second = new EnumType(
                namespace: 'Test.Service',
                name: 'Status',
                members: [new EnumMember('Open', 1), new EnumMember('Closed', 2)],
            );

            $builder = (new EdmBuilder)
                ->namespace('Test.Service')
                ->addEnumType($first);

            expect(fn () => $builder->addEnumType($second))
                ->toThrow(\LogicException::class, 'EnumType "Test.Service.Status"');
        });

        it('auto-registers enum types referenced by an added entity type', function () {
            $tier = new EnumType(
                namespace: 'Test.Service',
                name: 'LicenseTier',
                members: [new EnumMember('Single', 1), new EnumMember('Platform', 2)],
            );
            $entityType = new EntityType(
                namespace: 'Test.Service',
                name: 'License',
                declaredProperties: [
                    new Property('id',   new PrimitiveType(EdmPrimitiveType::Int64)),
                    new Property('tier', $tier),
                ],
            );

            $edmx = (new EdmBuilder)
                ->namespace('Test.Service')
                ->addEntityType($entityType)
                ->build();

            $schema = $edmx->getSchemas()['Test.Service'];
            expect($schema->getEnumTypes())->toHaveCount(1);
            expect($schema->getEnumTypes()[0])->toBe($tier);
        });

        it('throws on a same-name collision with a different underlying type', function () {
            $first = new EnumType(
                namespace: 'Test.Service',
                name: 'Tier',
                underlyingType: EdmPrimitiveType::Int32,
                members: [new EnumMember('Single', 1)],
            );
            $second = new EnumType(
                namespace: 'Test.Service',
                name: 'Tier',
                underlyingType: EdmPrimitiveType::Byte,
                members: [new EnumMember('Single', 1)],
            );

            $builder = (new EdmBuilder)
                ->namespace('Test.Service')
                ->addEnumType($first);

            expect(fn () => $builder->addEnumType($second))->toThrow(\LogicException::class);
        });
    });

    describe('double-build guard', function () {
        it('throws LogicException when build() is called a second time', function () {
            $builder = (new EdmBuilder)->namespace('Test.Service');
            $builder->build();

            expect(fn () => $builder->build())->toThrow(\LogicException::class);
        });

        it('throws LogicException when a setter is called after build()', function () {
            $builder = (new EdmBuilder)->namespace('Test.Service');
            $builder->build();

            expect(fn () => $builder->version('4.01'))->toThrow(\LogicException::class);
        });
    });
});
