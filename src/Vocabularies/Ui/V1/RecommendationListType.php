<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Reference to a recommendation list
 */
final readonly class RecommendationListType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.RecommendationListType';

    public const array FIELDS = [
        'CollectionPath' => ['collectionPath', 'Edm.String', true],
        'RankProperty' => ['rankProperty', 'Edm.String', true],
        'Binding' => ['binding', 'Collection(com.sap.vocabularies.UI.v1.RecommendationBinding)', false],
    ];

    /**
     * @param list<RecommendationBinding>|Path|null $binding
     */
    public function __construct(
        public string|Path|null $collectionPath = null,
        public string|Path|null $rankProperty = null,
        public array|Path|null $binding = null,
    ) {}
}
