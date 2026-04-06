<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Specifies how to get a list of acceptable values for a property or parameter
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class ValueList implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.ValueList';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        PropertyInterface::class,
        FunctionParameterInterface::class,
    ];

    public function __construct(
        public readonly bool $distinctValuesSupported,
        public readonly bool $searchSupported,
        public readonly array $parameters,
        public readonly ?string $label = null,
        public readonly ?string $collectionPath = null,
        public readonly ?string $relativeCollectionPath = null,
        public readonly ?string $collectionRoot = null,
        public readonly ?int $fetchValues = null,
        public readonly ?string $presentationVariantQualifier = null,
        public readonly ?string $selectionVariantQualifier = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Common.ValueListType',
            new PropertyValue('Label', new ConstantAnnotationValue('String', (string) $this->label)),
            new PropertyValue('CollectionPath', new ConstantAnnotationValue('String', (string) $this->collectionPath)),
            new PropertyValue('RelativeCollectionPath', new ConstantAnnotationValue('NavigationPropertyPath', (string) $this->relativeCollectionPath)),
            new PropertyValue('CollectionRoot', new ConstantAnnotationValue('String', (string) $this->collectionRoot)),
            new PropertyValue('DistinctValuesSupported', new ConstantAnnotationValue('Boolean', (string) $this->distinctValuesSupported)),
            new PropertyValue('SearchSupported', new ConstantAnnotationValue('Boolean', (string) $this->searchSupported)),
            new PropertyValue('FetchValues', new ConstantAnnotationValue('String', (string) $this->fetchValues)),
            new PropertyValue('PresentationVariantQualifier', new ConstantAnnotationValue('String', (string) $this->presentationVariantQualifier)),
            new PropertyValue('SelectionVariantQualifier', new ConstantAnnotationValue('String', (string) $this->selectionVariantQualifier)),
            new PropertyValue('Parameters', new ConstantAnnotationValue('String', (string) $this->parameters)),
        );
    }
}
