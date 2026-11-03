<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Information about an SAP Object Node Type
 */
final readonly class SAPObjectNodeTypeType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.SAPObjectNodeTypeType';

    public const array FIELDS = [
        'Name' => ['name', 'Edm.String', true],
    ];

    public function __construct(
        public string|Path|null $name = null,
    ) {}
}
