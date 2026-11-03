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
