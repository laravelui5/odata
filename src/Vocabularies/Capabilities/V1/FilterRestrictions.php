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
 * Restrictions on filter expressions
 * @see TypedAnnotationInterface
 */
final readonly class FilterRestrictions implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.FilterRestrictions';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        PropertyInterface::class,
        NavigationPropertyInterface::class,
    ];

    /**
     * @param list<string>|Path|null $requiredProperties
     * @param list<string>|Path|null $nonFilterableProperties
     * @param list<FilterExpressionRestrictionType>|Path|null $filterExpressionRestrictions
     */
    public function __construct(
        public bool|Path|null $filterable = null,
        public bool|Path|null $requiresFilter = null,
        public int|Path|null $maxLevels = null,
        public array|Path|null $requiredProperties = null,
        public array|Path|null $nonFilterableProperties = null,
        public array|Path|null $filterExpressionRestrictions = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new FilterRestrictionsType(
            filterable: $this->filterable,
            requiresFilter: $this->requiresFilter,
            maxLevels: $this->maxLevels,
            requiredProperties: $this->requiredProperties,
            nonFilterableProperties: $this->nonFilterableProperties,
            filterExpressionRestrictions: $this->filterExpressionRestrictions,
        ))->toRecord();
    }
}
