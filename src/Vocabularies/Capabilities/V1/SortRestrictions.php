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
 * Restrictions on orderby expressions
 * @see TypedAnnotationInterface
 */
final readonly class SortRestrictions implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.SortRestrictions';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        PropertyInterface::class,
        NavigationPropertyInterface::class,
    ];

    /**
     * @param list<string>|Path|null $ascendingOnlyProperties
     * @param list<string>|Path|null $descendingOnlyProperties
     * @param list<string>|Path|null $nonSortableProperties
     */
    public function __construct(
        public bool|Path|null $sortable = null,
        public array|Path|null $ascendingOnlyProperties = null,
        public array|Path|null $descendingOnlyProperties = null,
        public array|Path|null $nonSortableProperties = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new SortRestrictionsType(
            sortable: $this->sortable,
            ascendingOnlyProperties: $this->ascendingOnlyProperties,
            descendingOnlyProperties: $this->descendingOnlyProperties,
            nonSortableProperties: $this->nonSortableProperties,
        ))->toRecord();
    }
}
