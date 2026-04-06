<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Parameters for client-calculated criticality, alternative to UI.Criticality
 * @see TypedAnnotationInterface
 */
final readonly class CriticalityCalculation implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.CriticalityCalculation';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [];

    public function __construct(
        public readonly bool $isRelativeDifference,
        public readonly mixed $improvementDirection,
        public readonly array $constantThresholds,
        public readonly mixed $referenceValue = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'UI.CriticalityCalculationType',
            new PropertyValue('ReferenceValue', new ConstantAnnotationValue('String', (string) $this->referenceValue)),
            new PropertyValue('IsRelativeDifference', new ConstantAnnotationValue('Boolean', (string) $this->isRelativeDifference)),
            new PropertyValue('ImprovementDirection', new ConstantAnnotationValue('String', (string) $this->improvementDirection)),
            new PropertyValue('ConstantThresholds', new ConstantAnnotationValue('String', (string) $this->constantThresholds)),
        );
    }
}
