<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Annotation\CollectionAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Fixtures\Models\AnnotatedAirport;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Discovery\ModelDiscovery;
use LaravelUi5\OData\Tests\TestCase;

/**
 * Tests that PHP vocabulary attributes (#[Description], #[Hidden], #[Label],
 * #[SelectionFields], #[LineItem]) on Eloquent models are discovered by
 * ModelDiscovery and attached to EntityType / Property objects.
 */
uses(TestCase::class);

// ── Helpers ──────────────────────────────────────────────────────────────────

function discoverAnnotated(string ...$modelClasses): EntityTypeInterface
{
    $discovery = new ModelDiscovery();
    foreach ($modelClasses as $class) {
        $discovery->add($class);
    }

    $builder = (new EdmBuilder())->namespace('Test.Ns');
    $discovery->apply($builder, 'Test.Ns');
    $edmx = $builder->build();

    $types = $edmx->getSchemas()['Test.Ns']->getEntityTypes();

    return $types[0];
}

// ── Entity type (class-level) annotations ────────────────────────────────────

describe('Annotation discovery → entity type', function () {
    it('discovers class-level Description annotation', function () {
        $type = discoverAnnotated(AnnotatedAirport::class);

        $annotation = $type->getAnnotation('Org.OData.Core.V1.Description');

        expect($annotation)->not->toBeNull()
            ->and($annotation)->toBeInstanceOf(AnnotationInterface::class)
            ->and($annotation->getValue())->toBeInstanceOf(ConstantAnnotationValue::class)
            ->and($annotation->getValue()->getValue())->toBe('An airport with IATA code');
    });

    it('discovers class-level SelectionFields annotation', function () {
        $type = discoverAnnotated(AnnotatedAirport::class);

        $annotation = $type->getAnnotation('com.sap.vocabularies.UI.v1.SelectionFields');

        expect($annotation)->not->toBeNull()
            ->and($annotation->getValue())->toBeInstanceOf(CollectionAnnotationValue::class)
            ->and($annotation->getValue()->count())->toBe(2);

        $items = $annotation->getValue()->getItems();
        expect($items[0]->getValue())->toBe('code')
            ->and($items[1]->getValue())->toBe('name');
    });

    it('discovers class-level LineItem annotation', function () {
        $type = discoverAnnotated(AnnotatedAirport::class);

        $annotation = $type->getAnnotation('com.sap.vocabularies.UI.v1.LineItem');

        expect($annotation)->not->toBeNull()
            ->and($annotation->getValue())->toBeInstanceOf(CollectionAnnotationValue::class)
            ->and($annotation->getValue()->count())->toBe(2);
    });

    it('discovers multiple class-level annotations', function () {
        $type = discoverAnnotated(AnnotatedAirport::class);

        // Description + SelectionFields + LineItem = 3 class-level annotations
        expect($type->getAnnotations())->toHaveCount(3);
    });
});

// ── Property-level annotations ───────────────────────────────────────────────

describe('Annotation discovery → properties', function () {
    it('discovers Label annotation on a property', function () {
        $type = discoverAnnotated(AnnotatedAirport::class);
        $codeProp = $type->getProperty('code');

        $annotation = $codeProp->getAnnotation('com.sap.vocabularies.Common.v1.Label');

        expect($annotation)->not->toBeNull()
            ->and($annotation->getValue())->toBeInstanceOf(ConstantAnnotationValue::class)
            ->and($annotation->getValue()->getValue())->toBe('IATA Code');
    });

    it('discovers Description annotation on a property', function () {
        $type = discoverAnnotated(AnnotatedAirport::class);
        $codeProp = $type->getProperty('code');

        $annotation = $codeProp->getAnnotation('Org.OData.Core.V1.Description');

        expect($annotation)->not->toBeNull()
            ->and($annotation->getValue()->getValue())->toBe('The IATA airport code');
    });

    it('discovers multiple annotations on a single property', function () {
        $type = discoverAnnotated(AnnotatedAirport::class);
        $codeProp = $type->getProperty('code');

        // Label + Description = 2
        expect($codeProp->getAnnotations())->toHaveCount(2);
    });

    it('discovers Hidden marker annotation on a property', function () {
        $type = discoverAnnotated(AnnotatedAirport::class);
        $countryIdProp = $type->getProperty('country_id');

        $annotation = $countryIdProp->getAnnotation('com.sap.vocabularies.UI.v1.Hidden');

        expect($annotation)->not->toBeNull()
            ->and($annotation->getValue())->toBeNull(); // marker — no value
    });

    it('leaves unannotated properties without annotations', function () {
        $type = discoverAnnotated(AnnotatedAirport::class);

        // Properties without PHP attribute annotations (DB-only columns)
        // The 'id' column has no model property with annotations
        $idProp = $type->getProperty('id');

        expect($idProp->getAnnotations())->toBe([]);
    });
});

// ── Unannotated models are unaffected ────────────────────────────────────────

describe('Annotation discovery → unannotated models', function () {
    it('produces no annotations on a model without vocabulary attributes', function () {
        $type = discoverAnnotated(Flight::class);

        expect($type->getAnnotations())->toBe([]);
    });

    it('produces no property annotations on unannotated model', function () {
        $type = discoverAnnotated(Flight::class);

        foreach ($type->getDeclaredProperties() as $prop) {
            expect($prop->getAnnotations())->toBe([]);
        }
    });
});
