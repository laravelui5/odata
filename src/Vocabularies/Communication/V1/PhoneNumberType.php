<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class PhoneNumberType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Communication.v1.PhoneNumberType';

    public const array FIELDS = [
        'uri' => ['uri', 'Edm.String', false],
        'type' => ['type', 'com.sap.vocabularies.Communication.v1.PhoneType', false],
    ];

    /**
     * @param string|Path|null $uri This SHOULD use the tel: URL schema defined in RFC3966
     * @param PhoneType|list<PhoneType>|Path|null $type Telephone type
     */
    public function __construct(
        public string|Path|null $uri = null,
        public PhoneType|array|Path|null $type = null,
    ) {}
}
