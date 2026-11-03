<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class UrlType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Communication.v1.UrlType';

    public const array FIELDS = [
        'uri' => ['uri', 'Edm.String', false],
        'type' => ['type', 'com.sap.vocabularies.Communication.v1.ContactInformationType', false],
    ];

    /**
     * @param string|Path|null $uri This MUST use the URL schema defined in RFC3986
     * @param ContactInformationType|list<ContactInformationType>|Path|null $type URL type
     */
    public function __construct(
        public string|Path|null $uri = null,
        public ContactInformationType|array|Path|null $type = null,
    ) {}
}
