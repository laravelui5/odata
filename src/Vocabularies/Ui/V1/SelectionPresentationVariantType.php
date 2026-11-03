<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class SelectionPresentationVariantType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.SelectionPresentationVariantType';

    public const array FIELDS = [
        'ID' => ['id', 'Edm.String', false],
        'Text' => ['text', 'Edm.String', false],
        'SelectionVariant' => ['selectionVariant', 'com.sap.vocabularies.UI.v1.SelectionVariantType', true],
        'PresentationVariant' => ['presentationVariant', 'com.sap.vocabularies.UI.v1.PresentationVariantType', true],
    ];

    /**
     * @param string|Path|null $id Optional identifier to reference this variant from an external context
     * @param string|Path|null $text Name of the bundling variant
     * @param SelectionVariantType|Path|null $selectionVariant Selection variant, either specified inline or referencing another annotation via Path
     * @param PresentationVariantType|Path|null $presentationVariant Presentation variant, either specified inline or referencing another annotation via Path
     */
    public function __construct(
        public string|Path|null $id = null,
        public string|Path|null $text = null,
        public SelectionVariantType|Path|null $selectionVariant = null,
        public PresentationVariantType|Path|null $presentationVariant = null,
    ) {}
}
