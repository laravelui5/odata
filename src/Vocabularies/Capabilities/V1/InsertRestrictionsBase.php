<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

readonly class InsertRestrictionsBase extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.InsertRestrictionsBase';

    public const array FIELDS = [
        'Insertable' => ['insertable', 'Edm.Boolean', false],
        'MaxLevels' => ['maxLevels', 'Edm.Int32', false],
        'TypecastSegmentSupported' => ['typecastSegmentSupported', 'Edm.Boolean', false],
        'QueryOptions' => ['queryOptions', 'Org.OData.Capabilities.V1.ModificationQueryOptionsType', false],
        'CustomHeaders' => ['customHeaders', 'Collection(Org.OData.Capabilities.V1.CustomParameter)', false],
        'CustomQueryOptions' => ['customQueryOptions', 'Collection(Org.OData.Capabilities.V1.CustomParameter)', false],
        'Description' => ['description', 'Edm.String', false],
        'LongDescription' => ['longDescription', 'Edm.String', false],
        'ErrorResponses' => ['errorResponses', 'Collection(Org.OData.Capabilities.V1.HttpResponse)', false],
    ];

    /**
     * @param bool|Path|null $insertable Entities can be inserted
     * @param int|Path|null $maxLevels The maximum number of navigation properties that can be traversed when addressing the collection to insert into. A value of -1 indicates there is no restriction.
     * @param bool|Path|null $typecastSegmentSupported Entities of a specific derived type can be created by specifying a type-cast segment
     * @param ModificationQueryOptionsType|Path|null $queryOptions Support for query options with insert requests
     * @param list<CustomParameter>|Path|null $customHeaders Supported or required custom headers
     * @param list<CustomParameter>|Path|null $customQueryOptions Supported or required custom query options
     * @param string|Path|null $description A brief description of the request
     * @param string|Path|null $longDescription A long description of the request
     * @param list<HttpResponse>|Path|null $errorResponses Possible error responses returned by the request.
     */
    public function __construct(
        public bool|Path|null $insertable = null,
        public int|Path|null $maxLevels = null,
        public bool|Path|null $typecastSegmentSupported = null,
        public ModificationQueryOptionsType|Path|null $queryOptions = null,
        public array|Path|null $customHeaders = null,
        public array|Path|null $customQueryOptions = null,
        public string|Path|null $description = null,
        public string|Path|null $longDescription = null,
        public array|Path|null $errorResponses = null,
    ) {}
}
