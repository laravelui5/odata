<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;

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

    /**
     * @param mixed $acceptanceRangeLowValue Lowest value that is considered positive
     * @param mixed $acceptanceRangeHighValue Highest value that is considered positive
     * @param mixed $toleranceRangeLowValue Lowest value that is considered neutral
     * @param mixed $toleranceRangeHighValue Highest value that is considered neutral
     * @param mixed $deviationRangeLowValue Lowest value that is considered critical
     * @param mixed $deviationRangeHighValue Highest value that is considered critical
     * @param mixed $referenceValue Reference value for the calculation, e.g. number of sales for the last year
     * @param bool|Path|null $isRelativeDifference Calculate with a relative difference
     * @param ImprovementDirectionType|Path|null $improvementDirection Describes in which direction the value improves
     * @param list<LevelThresholdsType>|Path|null $constantThresholds List of thresholds depending on the aggregation level as a set of constant values
     */
    public function __construct(
        public mixed $acceptanceRangeLowValue = null,
        public mixed $acceptanceRangeHighValue = null,
        public mixed $toleranceRangeLowValue = null,
        public mixed $toleranceRangeHighValue = null,
        public mixed $deviationRangeLowValue = null,
        public mixed $deviationRangeHighValue = null,
        public mixed $referenceValue = null,
        public bool|Path|null $isRelativeDifference = null,
        public ImprovementDirectionType|Path|null $improvementDirection = null,
        public array|Path|null $constantThresholds = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new CriticalityCalculationType(
            acceptanceRangeLowValue: $this->acceptanceRangeLowValue,
            acceptanceRangeHighValue: $this->acceptanceRangeHighValue,
            toleranceRangeLowValue: $this->toleranceRangeLowValue,
            toleranceRangeHighValue: $this->toleranceRangeHighValue,
            deviationRangeLowValue: $this->deviationRangeLowValue,
            deviationRangeHighValue: $this->deviationRangeHighValue,
            referenceValue: $this->referenceValue,
            isRelativeDifference: $this->isRelativeDifference,
            improvementDirection: $this->improvementDirection,
            constantThresholds: $this->constantThresholds,
        ))->toRecord();
    }
}
