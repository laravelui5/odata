<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Vocabularies\Communication\V1\AddressType;

/**
 * Geographic location
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class GeoLocation implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.GeoLocation';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

    /**
     * @param float|Path|null $latitude Geographic latitude
     * @param float|Path|null $longitude Geographic longitude
     * @param mixed $location A point in a round-earth coordinate system
     * @param AddressType|Path|null $address vCard-style address
     */
    public function __construct(
        public float|Path|null $latitude = null,
        public float|Path|null $longitude = null,
        public mixed $location = null,
        public AddressType|Path|null $address = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new GeoLocationType(
            latitude: $this->latitude,
            longitude: $this->longitude,
            location: $this->location,
            address: $this->address,
        ))->toRecord();
    }
}
