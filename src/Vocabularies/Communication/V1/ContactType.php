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
     * @param string|Path|null $fn Full name
     * @param NameType|Path|null $n Name
     * @param string|Path|null $nickname Nickname
     * @param string|Path|null $photo Image or photograph
     * @param string|Path|null $bday Birthday
     * @param string|Path|null $anniversary Date of marriage, or equivalent
     * @param GenderType|Path|null $gender Sex and gender identity
     * @param string|Path|null $title Position or job title
     * @param string|Path|null $role Function or part played in a particular situation
     * @param string|Path|null $org Organization Name defined by X.520
     * @param string|Path|null $orgunit Organization Unit defined by X.520
     * @param KindType|Path|null $kind Kind of contact
     * @param string|Path|null $note Supplemental information or a comment associated with the contact
     * @param list<AddressType>|Path|null $adr Addresses
     * @param list<PhoneNumberType>|Path|null $tel Phone numbers
     * @param list<EmailAddressType>|Path|null $email Email addresses
     * @param list<GeoDataType>|Path|null $geo Geographic locations
     * @param list<UrlType>|Path|null $url URLs
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
