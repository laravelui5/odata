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
 * Restrictions on delete operations
 * @see TypedAnnotationInterface
 */
final readonly class DeleteRestrictions implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.DeleteRestrictions';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        SingletonInterface::class,
        PropertyInterface::class,
        NavigationPropertyInterface::class,
    ];

    /**
     * @param list<PermissionType>|Path|null $permissions
     * @param list<CustomParameter>|Path|null $customHeaders
     * @param list<CustomParameter>|Path|null $customQueryOptions
     * @param list<HttpResponse>|Path|null $errorResponses
     * @param list<string>|Path|null $nonDeletableNavigationProperties
     */
    public function __construct(
        public bool|Path|null $deletable = null,
        public int|Path|null $maxLevels = null,
        public bool|Path|null $filterSegmentSupported = null,
        public bool|Path|null $typecastSegmentSupported = null,
        public array|Path|null $permissions = null,
        public array|Path|null $customHeaders = null,
        public array|Path|null $customQueryOptions = null,
        public string|Path|null $description = null,
        public string|Path|null $longDescription = null,
        public array|Path|null $errorResponses = null,
        public array|Path|null $nonDeletableNavigationProperties = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new DeleteRestrictionsType(
            deletable: $this->deletable,
            maxLevels: $this->maxLevels,
            filterSegmentSupported: $this->filterSegmentSupported,
            typecastSegmentSupported: $this->typecastSegmentSupported,
            permissions: $this->permissions,
            customHeaders: $this->customHeaders,
            customQueryOptions: $this->customQueryOptions,
            description: $this->description,
            longDescription: $this->longDescription,
            errorResponses: $this->errorResponses,
            nonDeletableNavigationProperties: $this->nonDeletableNavigationProperties,
        ))->toRecord();
    }
}
