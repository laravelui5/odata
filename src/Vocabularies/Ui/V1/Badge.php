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
 * Information usually displayed in the form of a business card
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Badge implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.Badge';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

    public function __construct(
        public readonly mixed $headLine,
        public readonly mixed $title,
        public readonly ?string $imageUrl = null,
        public readonly ?string $typeImageUrl = null,
        public readonly mixed $mainInfo = null,
        public readonly mixed $secondaryInfo = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'UI.BadgeType',
            new PropertyValue('HeadLine', new ConstantAnnotationValue('String', (string) $this->headLine)),
            new PropertyValue('Title', new ConstantAnnotationValue('String', (string) $this->title)),
            new PropertyValue('ImageUrl', new ConstantAnnotationValue('String', (string) $this->imageUrl)),
            new PropertyValue('TypeImageUrl', new ConstantAnnotationValue('String', (string) $this->typeImageUrl)),
            new PropertyValue('MainInfo', new ConstantAnnotationValue('String', (string) $this->mainInfo)),
            new PropertyValue('SecondaryInfo', new ConstantAnnotationValue('String', (string) $this->secondaryInfo)),
        );
    }
}
