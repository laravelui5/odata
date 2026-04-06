<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Supplying a value for the action or function parameter is optional.
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class OptionalParameter implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Core.V1.OptionalParameter';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        FunctionParameterInterface::class,
    ];

    public function __construct(
        public readonly ?string $defaultValue = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Core.OptionalParameterType',
            new PropertyValue('DefaultValue', new ConstantAnnotationValue('String', (string) $this->defaultValue)),
        );
    }
}
