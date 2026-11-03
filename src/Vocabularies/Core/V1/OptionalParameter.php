<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;

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

    /**
     * @param string|Path|null $defaultValue Default value for an optional parameter of primitive or enumeration type, using the same rules as the `cast` function in URLs.
     */
    public function __construct(
        public string|Path|null $defaultValue = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): AnnotationValueInterface
    {
        return (new OptionalParameterType(
            defaultValue: $this->defaultValue,
        ))->toRecord();
    }
}
