<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

abstract readonly class ValueListParameter extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.ValueListParameter';

    public const array FIELDS = [
        'ValueListProperty' => ['valueListProperty', 'Edm.String', true],
    ];

    /**
     * @param string|Path|null $valueListProperty Path to property in the value list . Format is identical to PropertyPath annotations.
     */
    public function __construct(
        public string|Path|null $valueListProperty = null,
    ) {}
}
