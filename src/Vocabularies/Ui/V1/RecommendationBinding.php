<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class RecommendationBinding extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.RecommendationBinding';

    public const array FIELDS = [
        'LocalDataProperty' => ['localDataProperty', 'Edm.PropertyPath', true],
        'ValueListProperty' => ['valueListProperty', 'Edm.String', true],
    ];

    /**
     * @param string|Path|null $localDataProperty Path to editable property for which recommended values exist
     * @param string|Path|null $valueListProperty Path to property in the collection of recommended values. Format is identical to PropertyPath annotations.
     */
    public function __construct(
        public string|Path|null $localDataProperty = null,
        public string|Path|null $valueListProperty = null,
    ) {}
}
