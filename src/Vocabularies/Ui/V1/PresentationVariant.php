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
     * @param string|Path|null $id Optional identifier to reference this variant from an external context
     * @param string|Path|null $text Name of the presentation variant
     * @param int|Path|null $maxItems Maximum number of items that should be included in the result
     * @param list<SortOrderType>|Path|null $sortOrder Collection can be provided inline or as a reference to a Common.SortOrder annotation via Path
     * @param list<string>|Path|null $groupBy Sequence of groupable properties p1, p2, ... defining how the result is composed of instances representing groups, one for each combination of value properties in the queried collection. The sequence specifies a certain level of aggregation for the queried collection, and every group instance will provide aggregated values for properties that are aggregatable. Moreover, the series of sub-sequences (p1), (p1, p2), ... forms a leveled hierarchy, which may become relevant in combination with `InitialExpansionLevel`.
     * @param list<string>|Path|null $totalBy Sub-sequence q1, q2, ... of properties p1, p2, ... specified in GroupBy. With this, additional levels of aggregation are requested in addition to the most granular level defined by GroupBy: Every element in the series of sub-sequences (q1), (q1, q2), ... introduces an additional aggregation level included in the result.
     * @param list<string>|Path|null $total Aggregatable properties for which aggregated values should be provided for the additional aggregation levels specified in TotalBy.
     * @param list<string>|Path|null $dynamicTotal Dynamic properties introduced by annotations for which aggregated values should be provided for the additional aggregation levels specified in TotalBy
     * @param bool|Path|null $includeGrandTotal Result should include a grand total for the properties specified in Total
     * @param int|Path|null $initialExpansionLevel Level up to which the hierarchy defined for the queried collection should be expanded initially. The hierarchy may be implicitly imposed by the sequence of the GroupBy, or by an explicit hierarchy annotation.
     * @param list<string>|Path|null $visualizations Lists available visualization types. Currently supported types are `UI.LineItem`, `UI.CardItem`, `UI.Chart`, and `UI.DataPoint`. For each type, no more than a single annotation is meaningful. Multiple instances of the same visualization type shall be modeled with different presentation variants. A reference to `UI.Lineitem` should always be part of the collection (least common denominator for renderers). The first entry of the collection is the default visualization.
     * @param string|Path|null $recursiveHierarchyQualifier Qualifier of the recursive hierarchy that should be applied to the Visualization
     * @param list<string>|Path|null $requestAtLeast Properties that should always be included in the result of the queried collection
     * @param list<string>|Path|null $selectionFields Properties that should be presented for filtering a collection of entities. Can be provided inline or as a reference to a `UI.SelectionFields` annotation via Path.
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
