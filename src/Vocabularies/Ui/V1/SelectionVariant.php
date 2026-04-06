<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

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

    public function __construct(
        public readonly array $parameters,
        public readonly array $selectOptions,
        public readonly ?string $iD = null,
        public readonly ?string $text = null,
        public readonly ?string $filterExpression = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'UI.SelectionVariantType',
            new PropertyValue('ID', new ConstantAnnotationValue('String', (string) $this->iD)),
            new PropertyValue('Text', new ConstantAnnotationValue('String', (string) $this->text)),
            new PropertyValue('Parameters', new ConstantAnnotationValue('String', (string) $this->parameters)),
            new PropertyValue('FilterExpression', new ConstantAnnotationValue('String', (string) $this->filterExpression)),
            new PropertyValue('SelectOptions', new ConstantAnnotationValue('String', (string) $this->selectOptions)),
        );
    }
}
