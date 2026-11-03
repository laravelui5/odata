<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class KPIDetailType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.KPIDetailType';

    public const array FIELDS = [
        'DefaultPresentationVariant' => ['defaultPresentationVariant', 'com.sap.vocabularies.UI.v1.PresentationVariantType', false],
        'AlternativePresentationVariants' => ['alternativePresentationVariants', 'Collection(com.sap.vocabularies.UI.v1.PresentationVariantType)', false],
        'SemanticObject' => ['semanticObject', 'Edm.String', false],
        'Action' => ['action', 'Edm.String', false],
    ];

    /**
     * @param list<PresentationVariantType>|Path|null $alternativePresentationVariants
     */
    public function __construct(
        public PresentationVariantType|Path|null $defaultPresentationVariant = null,
        public array|Path|null $alternativePresentationVariants = null,
        public string|Path|null $semanticObject = null,
        public string|Path|null $action = null,
    ) {}
}
