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
     * @param PresentationVariantType|Path|null $defaultPresentationVariant Presentation variant, either specified inline or referencing another annotation via Path
     * @param list<PresentationVariantType>|Path|null $alternativePresentationVariants A list of alternative presentation variants, either specified inline or referencing another annotation via Path
     * @param string|Path|null $semanticObject Name of the Semantic Object. If not specified, use Semantic Object annotated at the property referenced in KPI/DataPoint/Value
     * @param string|Path|null $action Name of the Action on the Semantic Object. If not specified, let user choose which of the available actions to trigger.
     */
    public function __construct(
        public PresentationVariantType|Path|null $defaultPresentationVariant = null,
        public array|Path|null $alternativePresentationVariants = null,
        public string|Path|null $semanticObject = null,
        public string|Path|null $action = null,
    ) {}
}
