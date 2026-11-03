<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeDefinitionInterface;

/**
 * Specifies how to get a list of recommended values for a property or parameter
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::TARGET_CLASS)]
final readonly class RecommendationList implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.RecommendationList';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        PropertyInterface::class,
        FunctionParameterInterface::class,
        TypeDefinitionInterface::class,
    ];

    /**
     * @param string|Path|null $collectionPath Resource path of a collection of recommended values
     * @param string|Path|null $rankProperty Name of the property within the collection of recommended values that describes the rank of the recommendation
     * @param list<RecommendationBinding>|Path|null $binding List of pairs of a local property and recommended value property
     */
    public function __construct(
        public string|Path|null $collectionPath = null,
        public string|Path|null $rankProperty = null,
        public array|Path|null $binding = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new RecommendationListType(
            collectionPath: $this->collectionPath,
            rankProperty: $this->rankProperty,
            binding: $this->binding,
        ))->toRecord();
    }
}
