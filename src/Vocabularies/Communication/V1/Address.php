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
 * Address
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Address implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Communication.v1.Address';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
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
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new AddressType(
            building: $this->building,
            street: $this->street,
            district: $this->district,
            locality: $this->locality,
            region: $this->region,
            code: $this->code,
            country: $this->country,
            pobox: $this->pobox,
            ext: $this->ext,
            careof: $this->careof,
            label: $this->label,
            type: $this->type,
        ))->toRecord();
    }
}
