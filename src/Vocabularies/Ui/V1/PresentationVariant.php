<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

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

    public function __construct(
        public readonly array $sortOrder,
        public readonly array $groupBy,
        public readonly array $totalBy,
        public readonly array $total,
        public readonly array $dynamicTotal,
        public readonly bool $includeGrandTotal,
        public readonly int $initialExpansionLevel,
        public readonly array $visualizations,
        public readonly array $requestAtLeast,
        public readonly array $selectionFields,
        public readonly ?string $iD = null,
        public readonly ?string $text = null,
        public readonly ?int $maxItems = null,
        public readonly ?string $recursiveHierarchyQualifier = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'UI.PresentationVariantType',
            new PropertyValue('ID', new ConstantAnnotationValue('String', (string) $this->iD)),
            new PropertyValue('Text', new ConstantAnnotationValue('String', (string) $this->text)),
            new PropertyValue('MaxItems', new ConstantAnnotationValue('Integer', (string) $this->maxItems)),
            new PropertyValue('SortOrder', new ConstantAnnotationValue('String', (string) $this->sortOrder)),
            new PropertyValue('GroupBy', new ConstantAnnotationValue('String', (string) $this->groupBy)),
            new PropertyValue('TotalBy', new ConstantAnnotationValue('String', (string) $this->totalBy)),
            new PropertyValue('Total', new ConstantAnnotationValue('String', (string) $this->total)),
            new PropertyValue('DynamicTotal', new ConstantAnnotationValue('String', (string) $this->dynamicTotal)),
            new PropertyValue('IncludeGrandTotal', new ConstantAnnotationValue('Boolean', (string) $this->includeGrandTotal)),
            new PropertyValue('InitialExpansionLevel', new ConstantAnnotationValue('Integer', (string) $this->initialExpansionLevel)),
            new PropertyValue('Visualizations', new ConstantAnnotationValue('String', (string) $this->visualizations)),
            new PropertyValue('RecursiveHierarchyQualifier', new ConstantAnnotationValue('String', (string) $this->recursiveHierarchyQualifier)),
            new PropertyValue('RequestAtLeast', new ConstantAnnotationValue('String', (string) $this->requestAtLeast)),
            new PropertyValue('SelectionFields', new ConstantAnnotationValue('String', (string) $this->selectionFields)),
        );
    }
}
