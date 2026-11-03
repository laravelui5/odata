<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class EmailAddressType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Communication.v1.EmailAddressType';

    public const array FIELDS = [
        'address' => ['address', 'Edm.String', false],
        'type' => ['type', 'com.sap.vocabularies.Communication.v1.ContactInformationType', false],
    ];

    /**
     * @param string|Path|null $address Email address
     * @param ContactInformationType|list<ContactInformationType>|Path|null $type Address type
     */
    public function __construct(
        public string|Path|null $address = null,
        public ContactInformationType|array|Path|null $type = null,
    ) {}
}
