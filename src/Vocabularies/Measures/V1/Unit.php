<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Measures\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\AnnotationValueWriter;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LogicException;

/**
 * The unit of measure for this measured quantity, e.g. cm for centimeters or % for percentages
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class Unit implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Measures.V1.Unit';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        FunctionParameterInterface::class,
        PropertyInterface::class,
    ];

    public function __construct(
        public string|Path|null $value = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        if ($this->value === null) {
            throw new LogicException('The term Org.OData.Measures.V1.Unit requires a value.');
        }
        return AnnotationValueWriter::write($this->value, 'Edm.String');
    }
}
