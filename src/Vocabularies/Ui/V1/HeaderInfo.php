<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

/**
 * Information for the header area of an entity representation. HeaderInfo is mandatory for main entity types of the model
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class HeaderInfo implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.HeaderInfo';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

    /**
     * @param string|Path|null $typeName Name of the main entity type
     * @param string|Path|null $typeNamePlural Plural form of the name of the main entity type
     * @param DataFieldAbstract|Path|null $title Title, e.g. for overview pages
     * @param DataFieldAbstract|Path|null $description Description, e.g. for overview pages
     * @param mixed $image Image for an instance of the entity type. If the property has a valid value, it can be used for the visualization of the instance. If it is not available or not valid the value of the property `ImageUrl` can be used instead.
     * @param string|Path|null $imageUrl Image URL for an instance of the entity type. If the property has a valid value, it can be used for the visualization of the instance. If it is not available or not valid the value of the property `TypeImageUrl` can be used instead.
     * @param string|Path|null $typeImageUrl Image URL for the entity type
     * @param string|Path|null $initials Latin letters to be used in case no `Image`, `ImageUrl`, or `TypeImageUrl` is present
     */
    public function __construct(
        public string|Path|null $typeName = null,
        public string|Path|null $typeNamePlural = null,
        public DataFieldAbstract|Path|null $title = null,
        public DataFieldAbstract|Path|null $description = null,
        public mixed $image = null,
        public string|Path|null $imageUrl = null,
        public string|Path|null $typeImageUrl = null,
        public string|Path|null $initials = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new HeaderInfoType(
            typeName: $this->typeName,
            typeNamePlural: $this->typeNamePlural,
            title: $this->title,
            description: $this->description,
            image: $this->image,
            imageUrl: $this->imageUrl,
            typeImageUrl: $this->typeImageUrl,
            initials: $this->initials,
        ))->toRecord();
    }
}
