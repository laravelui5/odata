<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Vocabularies\Core\V1\Description;

describe('Description annotation', function () {
    it('produces the correct fully qualified term name', function () {
        $annotation = (new Description('the test value'))->toAnnotation();

        expect($annotation->getTerm())->toBe('Org.OData.Core.V1.Description');
    });

    it('produces a ConstantAnnotationValue', function () {
        $annotation = (new Description('the test value'))->toAnnotation();

        expect($annotation->getValue())->toBeInstanceOf(ConstantAnnotationValue::class);
    });

    it('produces a String kind', function () {
        /** @var ConstantAnnotationValue $value */
        $value = (new Description('the test value'))->toAnnotation()->getValue();

        expect($value->getKind())->toBe('String');
    });

    it('carries the value through', function () {
        /** @var ConstantAnnotationValue $value */
        $value = (new Description('the test value'))->toAnnotation()->getValue();

        expect($value->getValue())->toBe('the test value');
    });

    it('has no qualifier by default', function () {
        $annotation = (new Description('text'))->toAnnotation();

        expect($annotation->getQualifier())->toBeNull();
    });

    it('forwards a qualifier', function () {
        $annotation = (new Description('text', qualifier: 'Mobile'))->toAnnotation();

        expect($annotation->getQualifier())->toBe('Mobile');
    });
});
