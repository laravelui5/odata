<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Measures\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * The number of significant decimal places in the scale part (less than or equal to the number declared in the Scale facet)
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class Scale implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Measures.V1.Scale';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        FunctionParameterInterface::class,
        PropertyInterface::class,
    ];

    public function __construct(
        public readonly int $value,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new ConstantAnnotationValue('Integer', (string) $this->value);
    }
}
