<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;

/**
 * A dictionary of name-value pairs. Names must be valid property names, values may be restricted to a list of types via an annotation with term `Validation.OpenPropertyTypeConstraint`.
 */
final readonly class Dictionary extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Core.V1.Dictionary';

    public const array FIELDS = [];
}
