<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Vocabularies\Communication\V1\AddressType;

/**
 * Properties that define a geographic location
 */
final readonly class GeoLocationType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.GeoLocationType';

    public const array FIELDS = [
        'Latitude' => ['latitude', 'Edm.Double', false],
        'Longitude' => ['longitude', 'Edm.Double', false],
        'Location' => ['location', 'Edm.GeographyPoint', false],
        'Address' => ['address', 'com.sap.vocabularies.Communication.v1.AddressType', false],
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
    ) {}
}
