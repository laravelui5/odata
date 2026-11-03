<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class UpdateRestrictionsType extends UpdateRestrictionsBase
{
    public const string TYPE = 'Org.OData.Capabilities.V1.UpdateRestrictionsType';

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
        'NonUpdatableProperties' => ['nonUpdatableProperties', 'Collection(Edm.PropertyPath)', false],
        'NonUpdatableNavigationProperties' => ['nonUpdatableNavigationProperties', 'Collection(Edm.NavigationPropertyPath)', false],
        'RequiredProperties' => ['requiredProperties', 'Collection(Edm.PropertyPath)', false],
    ];

    /**
     * @param bool|Path|null $updatable Entities can be updated
     * @param bool|Path|null $upsertable Entities can be upserted
     * @param bool|Path|null $deltaUpdateSupported Entities can be inserted, updated, and deleted via a PATCH request with a delta payload
     * @param HttpMethod|list<HttpMethod>|Path|null $updateMethod Supported HTTP Methods (PUT or PATCH) for updating an entity. If null, PATCH SHOULD be supported and PUT MAY be supported.
     * @param bool|Path|null $filterSegmentSupported Members of collections can be updated via a PATCH request with a `/$filter(...)/$each` segment
     * @param bool|Path|null $typecastSegmentSupported Members of collections can be updated via a PATCH request with a type-cast segment and a `/$each` segment
     * @param int|Path|null $maxLevels The maximum number of navigation properties that can be traversed when addressing the collection or entity to update. A value of -1 indicates there is no restriction.
     * @param list<PermissionType>|Path|null $permissions Required permissions. One of the specified sets of scopes is required to perform the update.
     * @param ModificationQueryOptionsType|Path|null $queryOptions Support for query options with update requests
     * @param list<CustomParameter>|Path|null $customHeaders Supported or required custom headers
     * @param list<CustomParameter>|Path|null $customQueryOptions Supported or required custom query options
     * @param string|Path|null $description A brief description of the request
     * @param string|Path|null $longDescription A long description of the request
     * @param list<HttpResponse>|Path|null $errorResponses Possible error responses returned by the request.
     * @param list<string>|Path|null $nonUpdatableProperties These structural properties cannot be specified on update
     * @param list<string>|Path|null $nonUpdatableNavigationProperties These navigation properties do not allow rebinding
     * @param list<string>|Path|null $requiredProperties These structural properties must be specified on update
     */
    public function __construct(
        bool|Path|null $updatable = null,
        bool|Path|null $upsertable = null,
        bool|Path|null $deltaUpdateSupported = null,
        HttpMethod|array|Path|null $updateMethod = null,
        bool|Path|null $filterSegmentSupported = null,
        bool|Path|null $typecastSegmentSupported = null,
        int|Path|null $maxLevels = null,
        array|Path|null $permissions = null,
        ModificationQueryOptionsType|Path|null $queryOptions = null,
        array|Path|null $customHeaders = null,
        array|Path|null $customQueryOptions = null,
        string|Path|null $description = null,
        string|Path|null $longDescription = null,
        array|Path|null $errorResponses = null,
        public array|Path|null $nonUpdatableProperties = null,
        public array|Path|null $nonUpdatableNavigationProperties = null,
        public array|Path|null $requiredProperties = null,
    ) {
        parent::__construct(
            updatable: $updatable,
            upsertable: $upsertable,
            deltaUpdateSupported: $deltaUpdateSupported,
            updateMethod: $updateMethod,
            filterSegmentSupported: $filterSegmentSupported,
            typecastSegmentSupported: $typecastSegmentSupported,
            maxLevels: $maxLevels,
            permissions: $permissions,
            queryOptions: $queryOptions,
            customHeaders: $customHeaders,
            customQueryOptions: $customQueryOptions,
            description: $description,
            longDescription: $longDescription,
            errorResponses: $errorResponses,
        );
    }
}
