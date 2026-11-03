<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class InsertRestrictionsType extends InsertRestrictionsBase
{
    public const string TYPE = 'Org.OData.Capabilities.V1.InsertRestrictionsType';

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
        'NonInsertableProperties' => ['nonInsertableProperties', 'Collection(Edm.PropertyPath)', false],
        'NonInsertableNavigationProperties' => ['nonInsertableNavigationProperties', 'Collection(Edm.NavigationPropertyPath)', false],
        'RequiredProperties' => ['requiredProperties', 'Collection(Edm.PropertyPath)', false],
        'Permissions' => ['permissions', 'Collection(Org.OData.Capabilities.V1.PermissionType)', false],
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
     * @param list<string>|Path|null $nonInsertableProperties These structural properties cannot be specified on insert
     * @param list<string>|Path|null $nonInsertableNavigationProperties These navigation properties do not allow deep inserts
     * @param list<string>|Path|null $requiredProperties These structural properties must be specified on insert
     * @param list<PermissionType>|Path|null $permissions Required permissions. One of the specified sets of scopes is required to perform the insert.
     */
    public function __construct(
        bool|Path|null $insertable = null,
        int|Path|null $maxLevels = null,
        bool|Path|null $typecastSegmentSupported = null,
        ModificationQueryOptionsType|Path|null $queryOptions = null,
        array|Path|null $customHeaders = null,
        array|Path|null $customQueryOptions = null,
        string|Path|null $description = null,
        string|Path|null $longDescription = null,
        array|Path|null $errorResponses = null,
        public array|Path|null $nonInsertableProperties = null,
        public array|Path|null $nonInsertableNavigationProperties = null,
        public array|Path|null $requiredProperties = null,
        public array|Path|null $permissions = null,
    ) {
        parent::__construct(
            insertable: $insertable,
            maxLevels: $maxLevels,
            typecastSegmentSupported: $typecastSegmentSupported,
            queryOptions: $queryOptions,
            customHeaders: $customHeaders,
            customQueryOptions: $customQueryOptions,
            description: $description,
            longDescription: $longDescription,
            errorResponses: $errorResponses,
        );
    }
}
