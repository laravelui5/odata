<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Validation\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ConstraintType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Validation.V1.ConstraintType';

    public const array FIELDS = [
        'FailureMessage' => ['failureMessage', 'Edm.String', false],
        'Condition' => ['condition', 'Edm.Boolean', true],
    ];

    public function __construct(
        public string|Path|null $failureMessage = null,
        public bool|Path|null $condition = null,
    ) {}
}
