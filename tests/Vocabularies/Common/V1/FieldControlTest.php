<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Vocabularies\Common\V1\FieldControl;
use LaravelUi5\OData\Vocabularies\Common\V1\FieldControlType;

describe('FieldControl annotation', function () {
    it('produces the correct fully qualified term name', function () {
        $annotation = (new FieldControl(FieldControlType::Mandatory))->toAnnotation();

        expect($annotation->getTerm())->toBe('com.sap.vocabularies.Common.v1.FieldControl');
    });

    it('carries the member value as an Integer constant', function () {
        /** @var ConstantAnnotationValue $value */
        $value = (new FieldControl(FieldControlType::Mandatory))->toAnnotation()->getValue();

        expect($value->getKind())->toBe('Integer')
            ->and($value->getValue())->toBe('7');
    });

    // The vocabulary aliases Hidden = 0 to Inapplicable = 0. Generated as two
    // cases, the enum died with "Duplicate value" on first access.
    it('resolves the Hidden alias to Inapplicable', function () {
        /** @var ConstantAnnotationValue $value */
        $value = (new FieldControl(FieldControlType::Hidden))->toAnnotation()->getValue();

        expect(FieldControlType::Hidden)->toBe(FieldControlType::Inapplicable)
            ->and($value->getValue())->toBe('0');
    });
});
