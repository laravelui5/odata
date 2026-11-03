<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ReadRestrictionsType extends ReadRestrictionsBase
{
    public const string TYPE = 'Org.OData.Capabilities.V1.ReadRestrictionsType';

    public const array FIELDS = [
        'Readable' => ['readable', 'Edm.Boolean', false],
        'Permissions' => ['permissions', 'Collection(Org.OData.Capabilities.V1.PermissionType)', false],
        'CustomHeaders' => ['customHeaders', 'Collection(Org.OData.Capabilities.V1.CustomParameter)', false],
        'CustomQueryOptions' => ['customQueryOptions', 'Collection(Org.OData.Capabilities.V1.CustomParameter)', false],
        'Description' => ['description', 'Edm.String', false],
        'LongDescription' => ['longDescription', 'Edm.String', false],
        'ErrorResponses' => ['errorResponses', 'Collection(Org.OData.Capabilities.V1.HttpResponse)', false],
        'TypecastSegmentSupported' => ['typecastSegmentSupported', 'Edm.Boolean', false],
        'ReadByKeyRestrictions' => ['readByKeyRestrictions', 'Org.OData.Capabilities.V1.ReadByKeyRestrictionsType', false],
    ];

    /**
     * @param bool|Path|null $readable Entities can be retrieved
     * @param list<PermissionType>|Path|null $permissions Required permissions. One of the specified sets of scopes is required to read.
     * @param list<CustomParameter>|Path|null $customHeaders Supported or required custom headers
     * @param list<CustomParameter>|Path|null $customQueryOptions Supported or required custom query options
     * @param string|Path|null $description A brief description of the request
     * @param string|Path|null $longDescription A long description of the request
     * @param list<HttpResponse>|Path|null $errorResponses Possible error responses returned by the request.
     * @param bool|Path|null $typecastSegmentSupported Entities of a specific derived type can be read by specifying a type-cast segment
     * @param ReadByKeyRestrictionsType|Path|null $readByKeyRestrictions Restrictions for retrieving an entity by key
     */
    public function __construct(
        bool|Path|null $readable = null,
        array|Path|null $permissions = null,
        array|Path|null $customHeaders = null,
        array|Path|null $customQueryOptions = null,
        string|Path|null $description = null,
        string|Path|null $longDescription = null,
        array|Path|null $errorResponses = null,
        public bool|Path|null $typecastSegmentSupported = null,
        public ReadByKeyRestrictionsType|Path|null $readByKeyRestrictions = null,
    ) {
        parent::__construct(
            readable: $readable,
            permissions: $permissions,
            customHeaders: $customHeaders,
            customQueryOptions: $customQueryOptions,
            description: $description,
            longDescription: $longDescription,
            errorResponses: $errorResponses,
        );
    }
}
