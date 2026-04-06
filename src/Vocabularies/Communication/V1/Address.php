<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

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

    public function __construct(
        public readonly ?string $building = null,
        public readonly ?string $street = null,
        public readonly ?string $district = null,
        public readonly ?string $locality = null,
        public readonly ?string $region = null,
        public readonly ?string $code = null,
        public readonly ?string $country = null,
        public readonly ?string $pobox = null,
        public readonly ?string $ext = null,
        public readonly ?string $careof = null,
        public readonly ?string $label = null,
        public readonly mixed $type = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Communication.AddressType',
            new PropertyValue('building', new ConstantAnnotationValue('String', (string) $this->building)),
            new PropertyValue('street', new ConstantAnnotationValue('String', (string) $this->street)),
            new PropertyValue('district', new ConstantAnnotationValue('String', (string) $this->district)),
            new PropertyValue('locality', new ConstantAnnotationValue('String', (string) $this->locality)),
            new PropertyValue('region', new ConstantAnnotationValue('String', (string) $this->region)),
            new PropertyValue('code', new ConstantAnnotationValue('String', (string) $this->code)),
            new PropertyValue('country', new ConstantAnnotationValue('String', (string) $this->country)),
            new PropertyValue('pobox', new ConstantAnnotationValue('String', (string) $this->pobox)),
            new PropertyValue('ext', new ConstantAnnotationValue('String', (string) $this->ext)),
            new PropertyValue('careof', new ConstantAnnotationValue('String', (string) $this->careof)),
            new PropertyValue('label', new ConstantAnnotationValue('String', (string) $this->label)),
            new PropertyValue('type', new ConstantAnnotationValue('String', (string) $this->type)),
        );
    }
}
