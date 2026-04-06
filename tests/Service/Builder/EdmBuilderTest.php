<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\Type\EntityType;
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
