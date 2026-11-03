<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class OperationRestrictionsType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.OperationRestrictionsType';

    public const array FIELDS = [
        'FilterSegmentSupported' => ['filterSegmentSupported', 'Edm.Boolean', false],
        'Permissions' => ['permissions', 'Collection(Org.OData.Capabilities.V1.PermissionType)', false],
        'CustomHeaders' => ['customHeaders', 'Collection(Org.OData.Capabilities.V1.CustomParameter)', false],
        'CustomQueryOptions' => ['customQueryOptions', 'Collection(Org.OData.Capabilities.V1.CustomParameter)', false],
        'ErrorResponses' => ['errorResponses', 'Collection(Org.OData.Capabilities.V1.HttpResponse)', false],
    ];

    /**
     * @param bool|Path|null $filterSegmentSupported Bound action or function can be invoked on a collection-valued binding parameter path with a `/$filter(...)` segment
     * @param list<PermissionType>|Path|null $permissions Required permissions. One of the specified sets of scopes is required to invoke an action or function
     * @param list<CustomParameter>|Path|null $customHeaders Supported or required custom headers
     * @param list<CustomParameter>|Path|null $customQueryOptions Supported or required custom query options
     * @param list<HttpResponse>|Path|null $errorResponses Possible error responses returned by the request.
     */
    public function __construct(
        public bool|Path|null $filterSegmentSupported = null,
        public array|Path|null $permissions = null,
        public array|Path|null $customHeaders = null,
        public array|Path|null $customQueryOptions = null,
        public array|Path|null $errorResponses = null,
    ) {}
}
