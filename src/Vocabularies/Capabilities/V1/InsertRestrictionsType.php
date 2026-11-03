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
     * @param list<CustomParameter>|Path|null $customHeaders
     * @param list<CustomParameter>|Path|null $customQueryOptions
     * @param list<HttpResponse>|Path|null $errorResponses
     * @param list<string>|Path|null $nonInsertableProperties
     * @param list<string>|Path|null $nonInsertableNavigationProperties
     * @param list<string>|Path|null $requiredProperties
     * @param list<PermissionType>|Path|null $permissions
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
