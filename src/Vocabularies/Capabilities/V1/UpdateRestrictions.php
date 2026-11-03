<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;

/**
 * Restrictions on update operations
 * @see TypedAnnotationInterface
 */
final readonly class UpdateRestrictions implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.UpdateRestrictions';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        SingletonInterface::class,
        PropertyInterface::class,
        NavigationPropertyInterface::class,
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
        public array|Path|null $nonUpdatableProperties = null,
        public array|Path|null $nonUpdatableNavigationProperties = null,
        public array|Path|null $requiredProperties = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new UpdateRestrictionsType(
            updatable: $this->updatable,
            upsertable: $this->upsertable,
            deltaUpdateSupported: $this->deltaUpdateSupported,
            updateMethod: $this->updateMethod,
            filterSegmentSupported: $this->filterSegmentSupported,
            typecastSegmentSupported: $this->typecastSegmentSupported,
            maxLevels: $this->maxLevels,
            permissions: $this->permissions,
            queryOptions: $this->queryOptions,
            customHeaders: $this->customHeaders,
            customQueryOptions: $this->customQueryOptions,
            description: $this->description,
            longDescription: $this->longDescription,
            errorResponses: $this->errorResponses,
            nonUpdatableProperties: $this->nonUpdatableProperties,
            nonUpdatableNavigationProperties: $this->nonUpdatableNavigationProperties,
            requiredProperties: $this->requiredProperties,
        ))->toRecord();
    }
}
