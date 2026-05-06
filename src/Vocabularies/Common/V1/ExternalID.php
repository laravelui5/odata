<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * A human readable identifier for values of the annotated property or parameter. Value MUST be a dynamic expression when used as metadata annotation.
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class ExternalID implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.ExternalID';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        PropertyInterface::class,
        FunctionParameterInterface::class,
    ];

    public function __construct(
        public readonly EdmPrimitiveType $type,
        public readonly mixed $value = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        if ($this->value === null) {
            return null;
        }
        return match ($this->type) {
            EdmPrimitiveType::Byte,
            EdmPrimitiveType::SByte,
            EdmPrimitiveType::Int16,
            EdmPrimitiveType::Int32,
            EdmPrimitiveType::Int64      => new ConstantAnnotationValue('Integer', (string) $this->value),
            EdmPrimitiveType::Decimal    => new ConstantAnnotationValue('Decimal', (string) $this->value),
            EdmPrimitiveType::Double,
            EdmPrimitiveType::Single     => new ConstantAnnotationValue('Float', (string) $this->value),
            EdmPrimitiveType::Boolean    => new ConstantAnnotationValue('Boolean', $this->value ? 'true' : 'false'),
            EdmPrimitiveType::Date       => new ConstantAnnotationValue('Date', (string) $this->value),
            EdmPrimitiveType::DateTimeOffset => new ConstantAnnotationValue('DateTimeOffset', (string) $this->value),
            EdmPrimitiveType::TimeOfDay  => new ConstantAnnotationValue('TimeOfDay', (string) $this->value),
            EdmPrimitiveType::Duration   => new ConstantAnnotationValue('Duration', (string) $this->value),
            EdmPrimitiveType::Guid       => new ConstantAnnotationValue('Guid', (string) $this->value),
            default                       => new ConstantAnnotationValue('String', (string) $this->value),
        };
    }
}
