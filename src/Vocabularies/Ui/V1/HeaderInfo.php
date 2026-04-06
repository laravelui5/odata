<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

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

    public function __construct(
        public readonly string $typeName,
        public readonly string $typeNamePlural,
        public readonly mixed $title = null,
        public readonly mixed $description = null,
        public readonly mixed $image = null,
        public readonly ?string $imageUrl = null,
        public readonly ?string $typeImageUrl = null,
        public readonly ?string $initials = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'UI.HeaderInfoType',
            new PropertyValue('TypeName', new ConstantAnnotationValue('String', (string) $this->typeName)),
            new PropertyValue('TypeNamePlural', new ConstantAnnotationValue('String', (string) $this->typeNamePlural)),
            new PropertyValue('Title', new ConstantAnnotationValue('String', (string) $this->title)),
            new PropertyValue('Description', new ConstantAnnotationValue('String', (string) $this->description)),
            new PropertyValue('Image', new ConstantAnnotationValue('String', (string) $this->image)),
            new PropertyValue('ImageUrl', new ConstantAnnotationValue('String', (string) $this->imageUrl)),
            new PropertyValue('TypeImageUrl', new ConstantAnnotationValue('String', (string) $this->typeImageUrl)),
            new PropertyValue('Initials', new ConstantAnnotationValue('String', (string) $this->initials)),
        );
    }
}
