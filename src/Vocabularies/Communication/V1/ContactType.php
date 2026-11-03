<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ContactType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Communication.v1.ContactType';

    public const array FIELDS = [
        'fn' => ['fn', 'Edm.String', false],
        'n' => ['n', 'com.sap.vocabularies.Communication.v1.NameType', false],
        'nickname' => ['nickname', 'Edm.String', false],
        'photo' => ['photo', 'Edm.String', false],
        'bday' => ['bday', 'Edm.Date', false],
        'anniversary' => ['anniversary', 'Edm.Date', false],
        'gender' => ['gender', 'com.sap.vocabularies.Communication.v1.GenderType', false],
        'title' => ['title', 'Edm.String', false],
        'role' => ['role', 'Edm.String', false],
        'org' => ['org', 'Edm.String', false],
        'orgunit' => ['orgunit', 'Edm.String', false],
        'kind' => ['kind', 'com.sap.vocabularies.Communication.v1.KindType', false],
        'note' => ['note', 'Edm.String', false],
        'adr' => ['adr', 'Collection(com.sap.vocabularies.Communication.v1.AddressType)', false],
        'tel' => ['tel', 'Collection(com.sap.vocabularies.Communication.v1.PhoneNumberType)', false],
        'email' => ['email', 'Collection(com.sap.vocabularies.Communication.v1.EmailAddressType)', false],
        'geo' => ['geo', 'Collection(com.sap.vocabularies.Communication.v1.GeoDataType)', false],
        'url' => ['url', 'Collection(com.sap.vocabularies.Communication.v1.UrlType)', false],
    ];

    /**
     * @param list<AddressType>|Path|null $adr
     * @param list<PhoneNumberType>|Path|null $tel
     * @param list<EmailAddressType>|Path|null $email
     * @param list<GeoDataType>|Path|null $geo
     * @param list<UrlType>|Path|null $url
     */
    public function __construct(
        public string|Path|null $fn = null,
        public NameType|Path|null $n = null,
        public string|Path|null $nickname = null,
        public string|Path|null $photo = null,
        public string|Path|null $bday = null,
        public string|Path|null $anniversary = null,
        public GenderType|Path|null $gender = null,
        public string|Path|null $title = null,
        public string|Path|null $role = null,
        public string|Path|null $org = null,
        public string|Path|null $orgunit = null,
        public KindType|Path|null $kind = null,
        public string|Path|null $note = null,
        public array|Path|null $adr = null,
        public array|Path|null $tel = null,
        public array|Path|null $email = null,
        public array|Path|null $geo = null,
        public array|Path|null $url = null,
    ) {}
}
