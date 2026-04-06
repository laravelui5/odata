<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeEnum;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Define default values for action parameters
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class ParameterDefaultValue implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.ParameterDefaultValue';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        FunctionParameterInterface::class,
    ];

    public function __construct(
        public readonly PrimitiveTypeEnum $type,
        public readonly mixed $value = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        if ($this->value === null) {
            return null;
        }
        return match ($this->type) {
            PrimitiveTypeEnum::Byte,
            PrimitiveTypeEnum::SByte,
            PrimitiveTypeEnum::Int16,
            PrimitiveTypeEnum::Int32,
            PrimitiveTypeEnum::Int64      => new ConstantAnnotationValue('Integer', (string) $this->value),
            PrimitiveTypeEnum::Decimal    => new ConstantAnnotationValue('Decimal', (string) $this->value),
            PrimitiveTypeEnum::Double,
            PrimitiveTypeEnum::Single     => new ConstantAnnotationValue('Float', (string) $this->value),
            PrimitiveTypeEnum::Boolean    => new ConstantAnnotationValue('Boolean', $this->value ? 'true' : 'false'),
            PrimitiveTypeEnum::Date       => new ConstantAnnotationValue('Date', (string) $this->value),
            PrimitiveTypeEnum::DateTimeOffset => new ConstantAnnotationValue('DateTimeOffset', (string) $this->value),
            PrimitiveTypeEnum::TimeOfDay  => new ConstantAnnotationValue('TimeOfDay', (string) $this->value),
            PrimitiveTypeEnum::Duration   => new ConstantAnnotationValue('Duration', (string) $this->value),
            PrimitiveTypeEnum::Guid       => new ConstantAnnotationValue('Guid', (string) $this->value),
            default                       => new ConstantAnnotationValue('String', (string) $this->value),
        };
    }
}
