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
     * @param list<CustomParameter>|Path|null $customHeaders
     * @param list<CustomParameter>|Path|null $customQueryOptions
     * @param list<HttpResponse>|Path|null $errorResponses
     * @param list<string>|Path|null $nonInsertableProperties
     * @param list<string>|Path|null $nonInsertableNavigationProperties
     * @param list<string>|Path|null $requiredProperties
     * @param list<PermissionType>|Path|null $permissions
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
