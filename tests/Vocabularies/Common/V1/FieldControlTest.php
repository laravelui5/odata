<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Vocabularies\Common\V1\FieldControl;
use LaravelUi5\OData\Vocabularies\Common\V1\FieldControlType;

describe('FieldControl annotation', function () {
    it('produces the correct fully qualified term name', function () {
        $annotation = (new FieldControl(FieldControlType::Mandatory))->toAnnotation();

        expect($annotation->getTerm())->toBe('com.sap.vocabularies.Common.v1.FieldControl');
    });

    // CSDL XML 4.01 §14.3.7: an enumeration value is the qualified type, a slash and the member name.
    it('carries the member as an EnumMember constant', function () {
        /** @var ConstantAnnotationValue $value */
        $value = (new FieldControl(FieldControlType::Mandatory))->toAnnotation()->getValue();

        expect($value->getKind())->toBe('EnumMember')
            ->and($value->getValue())->toBe('com.sap.vocabularies.Common.v1.FieldControlType/Mandatory');
    });

    it('takes a path, the common case of a field control read per instance', function () {
        /** @var ConstantAnnotationValue $value */
        $value = (new FieldControl(new Path('fieldControl')))->toAnnotation()->getValue();

        expect($value->getKind())->toBe('Path')
            ->and($value->getValue())->toBe('fieldControl');
    });

    // The vocabulary aliases Hidden = 0 to Inapplicable = 0. Generated as two
    // cases, the enum died with "Duplicate value" on first access. Both names are
    // members of the type; the case keeps the first, so Inapplicable is written.
    it('resolves the Hidden alias to Inapplicable', function () {
        /** @var ConstantAnnotationValue $value */
        $value = (new FieldControl(FieldControlType::Hidden))->toAnnotation()->getValue();

        expect(FieldControlType::Hidden)->toBe(FieldControlType::Inapplicable)
            ->and($value->getValue())->toBe('com.sap.vocabularies.Common.v1.FieldControlType/Inapplicable');
    });
});
