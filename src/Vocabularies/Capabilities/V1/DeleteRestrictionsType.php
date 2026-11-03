<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class DeleteRestrictionsType extends DeleteRestrictionsBase
{
    public const string TYPE = 'Org.OData.Capabilities.V1.DeleteRestrictionsType';

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
        'NonDeletableNavigationProperties' => ['nonDeletableNavigationProperties', 'Collection(Edm.NavigationPropertyPath)', false],
    ];

    /**
     * @param list<PermissionType>|Path|null $permissions
     * @param list<CustomParameter>|Path|null $customHeaders
     * @param list<CustomParameter>|Path|null $customQueryOptions
     * @param list<HttpResponse>|Path|null $errorResponses
     * @param list<string>|Path|null $nonDeletableNavigationProperties
     */
    public function __construct(
        bool|Path|null $deletable = null,
        int|Path|null $maxLevels = null,
        bool|Path|null $filterSegmentSupported = null,
        bool|Path|null $typecastSegmentSupported = null,
        array|Path|null $permissions = null,
        array|Path|null $customHeaders = null,
        array|Path|null $customQueryOptions = null,
        string|Path|null $description = null,
        string|Path|null $longDescription = null,
        array|Path|null $errorResponses = null,
        public array|Path|null $nonDeletableNavigationProperties = null,
    ) {
        parent::__construct(
            deletable: $deletable,
            maxLevels: $maxLevels,
            filterSegmentSupported: $filterSegmentSupported,
            typecastSegmentSupported: $typecastSegmentSupported,
            permissions: $permissions,
            customHeaders: $customHeaders,
            customQueryOptions: $customQueryOptions,
            description: $description,
            longDescription: $longDescription,
            errorResponses: $errorResponses,
        );
    }
}
