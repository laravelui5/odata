<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

readonly class UpdateRestrictionsBase extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.UpdateRestrictionsBase';

    public const array FIELDS = [
        'Updatable' => ['updatable', 'Edm.Boolean', false],
        'Upsertable' => ['upsertable', 'Edm.Boolean', false],
        'DeltaUpdateSupported' => ['deltaUpdateSupported', 'Edm.Boolean', false],
        'UpdateMethod' => ['updateMethod', 'Org.OData.Capabilities.V1.HttpMethod', false],
        'FilterSegmentSupported' => ['filterSegmentSupported', 'Edm.Boolean', false],
        'TypecastSegmentSupported' => ['typecastSegmentSupported', 'Edm.Boolean', false],
        'MaxLevels' => ['maxLevels', 'Edm.Int32', false],
        'Permissions' => ['permissions', 'Collection(Org.OData.Capabilities.V1.PermissionType)', false],
        'QueryOptions' => ['queryOptions', 'Org.OData.Capabilities.V1.ModificationQueryOptionsType', false],
        'CustomHeaders' => ['customHeaders', 'Collection(Org.OData.Capabilities.V1.CustomParameter)', false],
        'CustomQueryOptions' => ['customQueryOptions', 'Collection(Org.OData.Capabilities.V1.CustomParameter)', false],
        'Description' => ['description', 'Edm.String', false],
        'LongDescription' => ['longDescription', 'Edm.String', false],
        'ErrorResponses' => ['errorResponses', 'Collection(Org.OData.Capabilities.V1.HttpResponse)', false],
    ];

    /**
     * @param HttpMethod|list<HttpMethod>|Path|null $updateMethod
     * @param list<PermissionType>|Path|null $permissions
     * @param list<CustomParameter>|Path|null $customHeaders
     * @param list<CustomParameter>|Path|null $customQueryOptions
     * @param list<HttpResponse>|Path|null $errorResponses
     */
    public function __construct(
        public bool|Path|null $updatable = null,
        public bool|Path|null $upsertable = null,
        public bool|Path|null $deltaUpdateSupported = null,
        public HttpMethod|array|Path|null $updateMethod = null,
        public bool|Path|null $filterSegmentSupported = null,
        public bool|Path|null $typecastSegmentSupported = null,
        public int|Path|null $maxLevels = null,
        public array|Path|null $permissions = null,
        public ModificationQueryOptionsType|Path|null $queryOptions = null,
        public array|Path|null $customHeaders = null,
        public array|Path|null $customQueryOptions = null,
        public string|Path|null $description = null,
        public string|Path|null $longDescription = null,
        public array|Path|null $errorResponses = null,
    ) {}
}
