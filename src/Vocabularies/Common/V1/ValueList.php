<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;

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

    /**
     * @param list<ValueListParameter>|Path|null $parameters
     */
    public function __construct(
        public string|Path|null $label = null,
        public string|Path|null $collectionPath = null,
        public string|Path|null $relativeCollectionPath = null,
        public string|Path|null $collectionRoot = null,
        public bool|Path|null $distinctValuesSupported = null,
        public bool|Path|null $searchSupported = null,
        public int|Path|null $fetchValues = null,
        public string|Path|null $presentationVariantQualifier = null,
        public string|Path|null $selectionVariantQualifier = null,
        public array|Path|null $parameters = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new ValueListType(
            label: $this->label,
            collectionPath: $this->collectionPath,
            relativeCollectionPath: $this->relativeCollectionPath,
            collectionRoot: $this->collectionRoot,
            distinctValuesSupported: $this->distinctValuesSupported,
            searchSupported: $this->searchSupported,
            fetchValues: $this->fetchValues,
            presentationVariantQualifier: $this->presentationVariantQualifier,
            selectionVariantQualifier: $this->selectionVariantQualifier,
            parameters: $this->parameters,
        ))->toRecord();
    }
}
