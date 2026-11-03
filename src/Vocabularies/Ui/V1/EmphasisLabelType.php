<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Assigns a label to the set of emphasized values and optionally also for non-emphasized values. This information can be used for semantic coloring.
 */
final readonly class EmphasisLabelType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.EmphasisLabelType';

    public const array FIELDS = [
        'EmphasizedValuesLabel' => ['emphasizedValuesLabel', 'Edm.String', true],
        'NonEmphasizedValuesLabel' => ['nonEmphasizedValuesLabel', 'Edm.String', false],
    ];

    public function __construct(
        public string|Path|null $emphasizedValuesLabel = null,
        public string|Path|null $nonEmphasizedValuesLabel = null,
    ) {}
}
