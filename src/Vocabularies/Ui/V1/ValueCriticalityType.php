<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Assigns a fixed criticality to a primitive value. This information can be used for semantic coloring.
 */
final readonly class ValueCriticalityType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.ValueCriticalityType';

    public const array FIELDS = [
        'Value' => ['value', 'Edm.PrimitiveType', false],
        'Criticality' => ['criticality', 'com.sap.vocabularies.UI.v1.CriticalityType', false],
    ];

    /**
     * @param mixed $value MUST be a fixed value of primitive type
     */
    public function __construct(
        public mixed $value = null,
        public CriticalityType|Path|null $criticality = null,
    ) {}
}
