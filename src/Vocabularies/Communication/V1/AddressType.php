<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class AddressType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Communication.v1.AddressType';

    public const array FIELDS = [
        'building' => ['building', 'Edm.String', false],
        'street' => ['street', 'Edm.String', false],
        'district' => ['district', 'Edm.String', false],
        'locality' => ['locality', 'Edm.String', false],
        'region' => ['region', 'Edm.String', false],
        'code' => ['code', 'Edm.String', false],
        'country' => ['country', 'Edm.String', false],
        'pobox' => ['pobox', 'Edm.String', false],
        'ext' => ['ext', 'Edm.String', false],
        'careof' => ['careof', 'Edm.String', false],
        'label' => ['label', 'Edm.String', false],
        'type' => ['type', 'com.sap.vocabularies.Communication.v1.ContactInformationType', false],
    ];

    /**
     * @param ContactInformationType|list<ContactInformationType>|Path|null $type
     */
    public function __construct(
        public string|Path|null $building = null,
        public string|Path|null $street = null,
        public string|Path|null $district = null,
        public string|Path|null $locality = null,
        public string|Path|null $region = null,
        public string|Path|null $code = null,
        public string|Path|null $country = null,
        public string|Path|null $pobox = null,
        public string|Path|null $ext = null,
        public string|Path|null $careof = null,
        public string|Path|null $label = null,
        public ContactInformationType|array|Path|null $type = null,
    ) {}
}
