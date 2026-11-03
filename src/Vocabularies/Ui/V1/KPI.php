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

/**
 * A Key Performance Indicator (KPI) bundles a SelectionVariant and a DataPoint, and provides details for progressive disclosure
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class KPI implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.KPI';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        EntityTypeInterface::class,
    ];

    /**
     * @param list<DataPointType>|Path|null $additionalDataPoints
     */
    public function __construct(
        public string|Path|null $id = null,
        public string|Path|null $shortDescription = null,
        public SelectionVariantType|Path|null $selectionVariant = null,
        public DataPointType|Path|null $dataPoint = null,
        public array|Path|null $additionalDataPoints = null,
        public KPIDetailType|Path|null $detail = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new KPIType(
            id: $this->id,
            shortDescription: $this->shortDescription,
            selectionVariant: $this->selectionVariant,
            dataPoint: $this->dataPoint,
            additionalDataPoints: $this->additionalDataPoints,
            detail: $this->detail,
        ))->toRecord();
    }
}
