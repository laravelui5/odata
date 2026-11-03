<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;

/**
 * Key property of a parameter entity type
 */
abstract readonly class ParameterAbstract extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.ParameterAbstract';

    public const array FIELDS = [];
}
