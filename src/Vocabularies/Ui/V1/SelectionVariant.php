<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

/**
 * A SelectionVariant denotes a combination of parameters and filters to query the annotated entity set
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class SelectionVariant implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.SelectionVariant';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        EntityTypeInterface::class,
    ];

    /**
     * @param list<ParameterAbstract>|Path|null $parameters
     * @param list<SelectOptionType>|Path|null $selectOptions
     */
    public function __construct(
        public string|Path|null $id = null,
        public string|Path|null $text = null,
        public array|Path|null $parameters = null,
        public string|Path|null $filterExpression = null,
        public array|Path|null $selectOptions = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new SelectionVariantType(
            id: $this->id,
            text: $this->text,
            parameters: $this->parameters,
            filterExpression: $this->filterExpression,
            selectOptions: $this->selectOptions,
        ))->toRecord();
    }
}
