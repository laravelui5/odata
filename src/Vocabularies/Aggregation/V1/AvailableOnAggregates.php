<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;

/**
 * This function is available on aggregated entities if the `RequiredProperties` are still defined
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class AvailableOnAggregates implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Aggregation.V1.AvailableOnAggregates';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        FunctionInterface::class,
    ];

    /**
     * @param list<string>|Path|null $requiredProperties Properties required to apply this function
     */
    public function __construct(
        public array|Path|null $requiredProperties = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new AvailableOnAggregatesType(
            requiredProperties: $this->requiredProperties,
        ))->toRecord();
    }
}
