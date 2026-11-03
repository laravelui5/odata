<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;

/**
 * Restrictions on insert operations
 * @see TypedAnnotationInterface
 */
final readonly class InsertRestrictions implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.InsertRestrictions';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        PropertyInterface::class,
        NavigationPropertyInterface::class,
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
        public bool|Path|null $insertable = null,
        public int|Path|null $maxLevels = null,
        public bool|Path|null $typecastSegmentSupported = null,
        public ModificationQueryOptionsType|Path|null $queryOptions = null,
        public array|Path|null $customHeaders = null,
        public array|Path|null $customQueryOptions = null,
        public string|Path|null $description = null,
        public string|Path|null $longDescription = null,
        public array|Path|null $errorResponses = null,
        public array|Path|null $nonInsertableProperties = null,
        public array|Path|null $nonInsertableNavigationProperties = null,
        public array|Path|null $requiredProperties = null,
        public array|Path|null $permissions = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new InsertRestrictionsType(
            insertable: $this->insertable,
            maxLevels: $this->maxLevels,
            typecastSegmentSupported: $this->typecastSegmentSupported,
            queryOptions: $this->queryOptions,
            customHeaders: $this->customHeaders,
            customQueryOptions: $this->customQueryOptions,
            description: $this->description,
            longDescription: $this->longDescription,
            errorResponses: $this->errorResponses,
            nonInsertableProperties: $this->nonInsertableProperties,
            nonInsertableNavigationProperties: $this->nonInsertableNavigationProperties,
            requiredProperties: $this->requiredProperties,
            permissions: $this->permissions,
        ))->toRecord();
    }
}
