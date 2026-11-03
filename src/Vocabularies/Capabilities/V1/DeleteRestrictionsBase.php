<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

readonly class DeleteRestrictionsBase extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.DeleteRestrictionsBase';

    public const array FIELDS = [
        'Deletable' => ['deletable', 'Edm.Boolean', false],
        'MaxLevels' => ['maxLevels', 'Edm.Int32', false],
        'FilterSegmentSupported' => ['filterSegmentSupported', 'Edm.Boolean', false],
        'TypecastSegmentSupported' => ['typecastSegmentSupported', 'Edm.Boolean', false],
        'Permissions' => ['permissions', 'Collection(Org.OData.Capabilities.V1.PermissionType)', false],
        'CustomHeaders' => ['customHeaders', 'Collection(Org.OData.Capabilities.V1.CustomParameter)', false],
        'CustomQueryOptions' => ['customQueryOptions', 'Collection(Org.OData.Capabilities.V1.CustomParameter)', false],
        'Description' => ['description', 'Edm.String', false],
        'LongDescription' => ['longDescription', 'Edm.String', false],
        'ErrorResponses' => ['errorResponses', 'Collection(Org.OData.Capabilities.V1.HttpResponse)', false],
    ];

    /**
     * @param bool|Path|null $deletable Entities can be deleted
     * @param int|Path|null $maxLevels The maximum number of navigation properties that can be traversed when addressing the collection to delete from or the entity to delete. A value of -1 indicates there is no restriction.
     * @param bool|Path|null $filterSegmentSupported Members of collections can be deleted via a DELETE request with a `/$filter(...)/$each` segment
     * @param bool|Path|null $typecastSegmentSupported Members of collections can be deleted via a DELETE request with a type-cast segment and a `/$each` segment
     * @param list<PermissionType>|Path|null $permissions Required permissions. One of the specified sets of scopes is required to perform the delete.
     * @param list<CustomParameter>|Path|null $customHeaders Supported or required custom headers
     * @param list<CustomParameter>|Path|null $customQueryOptions Supported or required custom query options
     * @param string|Path|null $description A brief description of the request
     * @param string|Path|null $longDescription A long description of the request
     * @param list<HttpResponse>|Path|null $errorResponses Possible error responses returned by the request.
     */
    public function __construct(
        public bool|Path|null $deletable = null,
        public int|Path|null $maxLevels = null,
        public bool|Path|null $filterSegmentSupported = null,
        public bool|Path|null $typecastSegmentSupported = null,
        public array|Path|null $permissions = null,
        public array|Path|null $customHeaders = null,
        public array|Path|null $customQueryOptions = null,
        public string|Path|null $description = null,
        public string|Path|null $longDescription = null,
        public array|Path|null $errorResponses = null,
    ) {}
}
