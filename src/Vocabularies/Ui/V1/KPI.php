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

    public function __construct(
        public readonly mixed $selectionVariant,
        public readonly mixed $dataPoint,
        public readonly array $additionalDataPoints,
        public readonly ?string $iD = null,
        public readonly ?string $shortDescription = null,
        public readonly mixed $detail = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'UI.KPIType',
            new PropertyValue('ID', new ConstantAnnotationValue('String', (string) $this->iD)),
            new PropertyValue('ShortDescription', new ConstantAnnotationValue('String', (string) $this->shortDescription)),
            new PropertyValue('SelectionVariant', new ConstantAnnotationValue('String', (string) $this->selectionVariant)),
            new PropertyValue('DataPoint', new ConstantAnnotationValue('String', (string) $this->dataPoint)),
            new PropertyValue('AdditionalDataPoints', new ConstantAnnotationValue('String', (string) $this->additionalDataPoints)),
            new PropertyValue('Detail', new ConstantAnnotationValue('String', (string) $this->detail)),
        );
    }
}
