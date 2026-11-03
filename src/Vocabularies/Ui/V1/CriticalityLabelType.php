<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Assigns a label to a criticality. This information can be used for semantic coloring.
 */
final readonly class CriticalityLabelType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.CriticalityLabelType';

    public const array FIELDS = [
        'Criticality' => ['criticality', 'com.sap.vocabularies.UI.v1.CriticalityType', true],
        'Label' => ['label', 'Edm.String', true],
    ];

    /**
     * @param string|Path|null $label Criticality label
     */
    public function __construct(
        public CriticalityType|Path|null $criticality = null,
        public string|Path|null $label = null,
    ) {}
}
