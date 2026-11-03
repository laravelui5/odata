<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

/**
 * Address book entry
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Contact implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Communication.v1.Contact';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
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
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): AnnotationValueInterface
    {
        return (new ContactType(
            fn: $this->fn,
            n: $this->n,
            nickname: $this->nickname,
            photo: $this->photo,
            bday: $this->bday,
            anniversary: $this->anniversary,
            gender: $this->gender,
            title: $this->title,
            role: $this->role,
            org: $this->org,
            orgunit: $this->orgunit,
            kind: $this->kind,
            note: $this->note,
            adr: $this->adr,
            tel: $this->tel,
            email: $this->email,
            geo: $this->geo,
            url: $this->url,
        ))->toRecord();
    }
}
