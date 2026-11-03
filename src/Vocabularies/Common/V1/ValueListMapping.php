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
 * Specifies the mapping between data service properties and value list properties
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class ValueListMapping implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.ValueListMapping';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        PropertyInterface::class,
        FunctionParameterInterface::class,
    ];

    /**
     * @param string|Path|null $label Headline for value list, fallback is the label of the property or parameter
     * @param string|Path|null $collectionPath Resource path of an OData collection with possible values, relative to the document containing the value list mapping
     * @param bool|Path|null $distinctValuesSupported Indicates that the value list supports a 'distinct' aggregation on the value list properties defined via ValueListParameterInOut and ValueListParameterOut
     * @param int|Path|null $fetchValues Hint on when to fetch values
     * @param string|Path|null $presentationVariantQualifier Alternative representation of a value help, e.g. as a bar chart
     * @param string|Path|null $selectionVariantQualifier Optional combination of parameters and filters to query the value help entity set
     * @param list<ValueListParameter>|Path|null $parameters Instructions on how to construct the value list request and consume response properties
     */
    public function __construct(
        public string|Path|null $label = null,
        public string|Path|null $collectionPath = null,
        public bool|Path|null $distinctValuesSupported = null,
        public int|Path|null $fetchValues = null,
        public string|Path|null $presentationVariantQualifier = null,
        public string|Path|null $selectionVariantQualifier = null,
        public array|Path|null $parameters = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new ValueListMappingType(
            label: $this->label,
            collectionPath: $this->collectionPath,
            distinctValuesSupported: $this->distinctValuesSupported,
            fetchValues: $this->fetchValues,
            presentationVariantQualifier: $this->presentationVariantQualifier,
            selectionVariantQualifier: $this->selectionVariantQualifier,
            parameters: $this->parameters,
        ))->toRecord();
    }
}
