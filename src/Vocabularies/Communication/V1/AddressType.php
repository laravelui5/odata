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
     * @param string|Path|null $building Building identifier
     * @param string|Path|null $street Street address
     * @param string|Path|null $district Territorial administrative organization in a large city
     * @param string|Path|null $locality City or similar
     * @param string|Path|null $region State, province, or similar
     * @param string|Path|null $code Postal code
     * @param string|Path|null $country Country name
     * @param string|Path|null $pobox Post office box
     * @param string|Path|null $ext Extended address (e.g., apartment or suite number)
     * @param string|Path|null $careof An intermediary who is responsible for transferring a piece of mail between the postal system and the final addressee
     * @param string|Path|null $label Delivery address label; plain-text string representing the formatted address, may contain line breaks
     * @param ContactInformationType|list<ContactInformationType>|Path|null $type Address type
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
