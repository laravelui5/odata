<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Vocabularies\Common\V1\SortOrderType;

/**
 * Defines how the result of a queried collection of entities is shaped and how this result is displayed
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class PresentationVariant implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.PresentationVariant';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        EntityTypeInterface::class,
    ];

    /**
     * @param list<SortOrderType>|Path|null $sortOrder
     * @param list<string>|Path|null $groupBy
     * @param list<string>|Path|null $totalBy
     * @param list<string>|Path|null $total
     * @param list<string>|Path|null $dynamicTotal
     * @param list<string>|Path|null $visualizations
     * @param list<string>|Path|null $requestAtLeast
     * @param list<string>|Path|null $selectionFields
     */
    public function __construct(
        public string|Path|null $id = null,
        public string|Path|null $text = null,
        public int|Path|null $maxItems = null,
        public array|Path|null $sortOrder = null,
        public array|Path|null $groupBy = null,
        public array|Path|null $totalBy = null,
        public array|Path|null $total = null,
        public array|Path|null $dynamicTotal = null,
        public bool|Path|null $includeGrandTotal = null,
        public int|Path|null $initialExpansionLevel = null,
        public array|Path|null $visualizations = null,
        public string|Path|null $recursiveHierarchyQualifier = null,
        public array|Path|null $requestAtLeast = null,
        public array|Path|null $selectionFields = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new PresentationVariantType(
            id: $this->id,
            text: $this->text,
            maxItems: $this->maxItems,
            sortOrder: $this->sortOrder,
            groupBy: $this->groupBy,
            totalBy: $this->totalBy,
            total: $this->total,
            dynamicTotal: $this->dynamicTotal,
            includeGrandTotal: $this->includeGrandTotal,
            initialExpansionLevel: $this->initialExpansionLevel,
            visualizations: $this->visualizations,
            recursiveHierarchyQualifier: $this->recursiveHierarchyQualifier,
            requestAtLeast: $this->requestAtLeast,
            selectionFields: $this->selectionFields,
        ))->toRecord();
    }
}
