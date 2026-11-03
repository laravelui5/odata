<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Base type containing recommendations for an entity type property
 */
abstract readonly class PropertyRecommendationType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.PropertyRecommendationType';

    public const array FIELDS = [
        'RecommendedFieldValue' => ['recommendedFieldValue', 'Edm.PrimitiveType', true],
        'RecommendedFieldDescription' => ['recommendedFieldDescription', 'Edm.String', false],
        'RecommendedFieldScoreValue' => ['recommendedFieldScoreValue', 'Edm.Decimal', false],
        'RecommendedFieldIsSuggestion' => ['recommendedFieldIsSuggestion', 'Edm.Boolean', false],
    ];

    /**
     * @param mixed $recommendedFieldValue Recommended value
     * @param string|Path|null $recommendedFieldDescription Description of the recommended value
     * @param float|Path|null $recommendedFieldScoreValue Confidence score of the recommended value
     * @param bool|Path|null $recommendedFieldIsSuggestion Whether the recommended value shall be suggested in the input field
     */
    public function __construct(
        public mixed $recommendedFieldValue = null,
        public string|Path|null $recommendedFieldDescription = null,
        public float|Path|null $recommendedFieldScoreValue = null,
        public bool|Path|null $recommendedFieldIsSuggestion = null,
    ) {}
}
