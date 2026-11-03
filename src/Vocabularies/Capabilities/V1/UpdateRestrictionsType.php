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
     * @param HttpMethod|list<HttpMethod>|Path|null $updateMethod
     * @param list<PermissionType>|Path|null $permissions
     * @param list<CustomParameter>|Path|null $customHeaders
     * @param list<CustomParameter>|Path|null $customQueryOptions
     * @param list<HttpResponse>|Path|null $errorResponses
     * @param list<string>|Path|null $nonUpdatableProperties
     * @param list<string>|Path|null $nonUpdatableNavigationProperties
     * @param list<string>|Path|null $requiredProperties
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
