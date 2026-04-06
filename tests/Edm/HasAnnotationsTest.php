<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Annotation\Annotation;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Container\EntityContainer;

// ── Tests ──────────────────────────────────────────────────────────────────────
// HasAnnotations is a trait; we exercise it through EntityContainer which uses it
// without any extra logic of its own.

describe('HasAnnotations', function () {

    describe('getAnnotations', function () {
        it('returns an empty array when no annotations are injected', function () {
            $container = new EntityContainer('C');
            expect($container->getAnnotations())->toBe([]);
        });

        it('returns all injected annotations', function () {
            $a1 = new Annotation('Org.OData.Core.V1.Description', null,
                new ConstantAnnotationValue('String', 'first'));
            $a2 = new Annotation('Org.OData.Core.V1.DefaultNamespace', null,
                new ConstantAnnotationValue('Bool', 'true'));

            $container = new EntityContainer('C', annotations: [$a1, $a2]);

            expect($container->getAnnotations())->toHaveCount(2);
        });
    });

    describe('getAnnotation — fully qualified term', function () {
        it('returns the annotation when the term matches exactly', function () {
            $annotation = new Annotation('Org.OData.Core.V1.Description', null,
                new ConstantAnnotationValue('String', 'hello'));

            $container = new EntityContainer('C', annotations: [$annotation]);

            expect($container->getAnnotation('Org.OData.Core.V1.Description'))
                ->toBe($annotation);
        });

        it('returns null for an unknown fully qualified term', function () {
            $container = new EntityContainer('C');
            expect($container->getAnnotation('Org.OData.Core.V1.Description'))->toBeNull();
        });
    });

    describe('getAnnotation — alias-qualified term', function () {
        it('resolves a known alias and finds the annotation', function () {
            // 'Core' is a registered alias for 'Org.OData.Core.V1'
            $annotation = new Annotation('Org.OData.Core.V1.Description', null,
                new ConstantAnnotationValue('String', 'via alias'));

            $container = new EntityContainer('C', annotations: [$annotation]);

            expect($container->getAnnotation('Core.Description'))->toBe($annotation);
        });

        it('returns null when the alias is unknown', function () {
            $annotation = new Annotation('Org.OData.Core.V1.Description', null,
                new ConstantAnnotationValue('String', 'value'));

            $container = new EntityContainer('C', annotations: [$annotation]);

            // 'Unknown' is not a registered alias — falls through to null
            expect($container->getAnnotation('Unknown.Description'))->toBeNull();
        });
    });

    describe('getAnnotation — qualifier matching', function () {
        it('returns the annotation when qualifier matches', function () {
            $annotation = new Annotation('Org.OData.Core.V1.Description', 'Mobile',
                new ConstantAnnotationValue('String', 'short'));

            $container = new EntityContainer('C', annotations: [$annotation]);

            expect($container->getAnnotation('Org.OData.Core.V1.Description', 'Mobile'))
                ->toBe($annotation);
        });

        it('does not return a qualified annotation when no qualifier is passed', function () {
            $annotation = new Annotation('Org.OData.Core.V1.Description', 'Mobile',
                new ConstantAnnotationValue('String', 'short'));

            $container = new EntityContainer('C', annotations: [$annotation]);

            // Passing null qualifier does not match a qualifier of 'Mobile'
            expect($container->getAnnotation('Org.OData.Core.V1.Description'))->toBeNull();
        });

        it('does not return an unqualified annotation when a qualifier is passed', function () {
            $annotation = new Annotation('Org.OData.Core.V1.Description', null,
                new ConstantAnnotationValue('String', 'unqualified'));

            $container = new EntityContainer('C', annotations: [$annotation]);

            expect($container->getAnnotation('Org.OData.Core.V1.Description', 'Mobile'))
                ->toBeNull();
        });

        it('returns the correct annotation among multiple qualifiers for the same term', function () {
            $desktop = new Annotation('Org.OData.Core.V1.Description', 'Desktop',
                new ConstantAnnotationValue('String', 'long form'));
            $mobile  = new Annotation('Org.OData.Core.V1.Description', 'Mobile',
                new ConstantAnnotationValue('String', 'short'));

            $container = new EntityContainer('C', annotations: [$desktop, $mobile]);

            expect($container->getAnnotation('Org.OData.Core.V1.Description', 'Mobile'))
                ->toBe($mobile)
                ->and($container->getAnnotation('Org.OData.Core.V1.Description', 'Desktop'))
                ->toBe($desktop);
        });
    });
});
