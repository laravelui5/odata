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

    public function __construct(
        public string|Path|null $localDataProperty = null,
        public string|Path|null $valueListProperty = null,
    ) {}
}
