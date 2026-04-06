<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Service\Discovery\AttributeReader;
use LaravelUi5\OData\Vocabularies\Core\V1\Description;
use LaravelUi5\OData\Vocabularies\Ui\V1\Hidden;

// ── Fixtures ──────────────────────────────────────────────────────────────────

#[Description('an entity description')]
final class AttributeReaderFixtureClass
{
    #[Hidden]
    public string $annotatedProperty = '';

    public string $unannotatedProperty = '';
}

// ── Tests ─────────────────────────────────────────────────────────────────────

describe('AttributeReader::readClass', function () {
    it('returns one annotation for a class-level TypedAnnotationInterface attribute', function () {
        $annotations = (new AttributeReader)->readClass(
            new ReflectionClass(AttributeReaderFixtureClass::class),
        );

        expect($annotations)->toHaveCount(1);
    });

    it('returns the correct fully qualified term name', function () {
        $annotations = (new AttributeReader)->readClass(
            new ReflectionClass(AttributeReaderFixtureClass::class),
        );

        expect($annotations[0]->getTerm())->toBe('Org.OData.Core.V1.Description');
    });

    it('carries the annotation value through', function () {
        $annotations = (new AttributeReader)->readClass(
            new ReflectionClass(AttributeReaderFixtureClass::class),
        );

        /** @var ConstantAnnotationValue $value */
        $value = $annotations[0]->getValue();

        expect($value)
            ->toBeInstanceOf(ConstantAnnotationValue::class)
            ->and($value->getValue())->toBe('an entity description');
    });

    it('returns an empty list when no TypedAnnotationInterface attributes are present', function () {
        $annotations = (new AttributeReader)->readClass(new ReflectionClass(stdClass::class));

        expect($annotations)->toBe([]);
    });
});

describe('AttributeReader::readProperty', function () {
    it('returns one annotation for a property-level TypedAnnotationInterface attribute', function () {
        $annotations = (new AttributeReader)->readProperty(
            new ReflectionProperty(AttributeReaderFixtureClass::class, 'annotatedProperty'),
        );

        expect($annotations)->toHaveCount(1);
    });

    it('returns the correct fully qualified term name', function () {
        $annotations = (new AttributeReader)->readProperty(
            new ReflectionProperty(AttributeReaderFixtureClass::class, 'annotatedProperty'),
        );

        expect($annotations[0]->getTerm())->toBe('com.sap.vocabularies.UI.v1.Hidden');
    });

    it('returns an empty list for a property without TypedAnnotationInterface attributes', function () {
        $annotations = (new AttributeReader)->readProperty(
            new ReflectionProperty(AttributeReaderFixtureClass::class, 'unannotatedProperty'),
        );

        expect($annotations)->toBe([]);
    });
});

describe('AttributeReader::readParameter', function () {
    it('returns one annotation for a parameter-level TypedAnnotationInterface attribute', function () {
        $fn          = function (#[Description('param description')] string $x): void {};
        $annotations = (new AttributeReader)->readParameter(
            (new ReflectionFunction($fn))->getParameters()[0],
        );

        expect($annotations)->toHaveCount(1);
    });

    it('returns the correct fully qualified term name for a parameter annotation', function () {
        $fn          = function (#[Description('param description')] string $x): void {};
        $annotations = (new AttributeReader)->readParameter(
            (new ReflectionFunction($fn))->getParameters()[0],
        );

        expect($annotations[0]->getTerm())->toBe('Org.OData.Core.V1.Description');
    });

    it('returns an empty list for a parameter without TypedAnnotationInterface attributes', function () {
        $fn          = function (string $x): void {};
        $annotations = (new AttributeReader)->readParameter(
            (new ReflectionFunction($fn))->getParameters()[0],
        );

        expect($annotations)->toBe([]);
    });
});
