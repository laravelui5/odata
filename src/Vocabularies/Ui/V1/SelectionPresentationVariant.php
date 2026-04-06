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
 * A SelectionPresentationVariant bundles a Selection Variant and a Presentation Variant
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class SelectionPresentationVariant implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.SelectionPresentationVariant';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        EntityTypeInterface::class,
    ];

    public function __construct(
        public readonly mixed $selectionVariant,
        public readonly mixed $presentationVariant,
        public readonly ?string $iD = null,
        public readonly ?string $text = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'UI.SelectionPresentationVariantType',
            new PropertyValue('ID', new ConstantAnnotationValue('String', (string) $this->iD)),
            new PropertyValue('Text', new ConstantAnnotationValue('String', (string) $this->text)),
            new PropertyValue('SelectionVariant', new ConstantAnnotationValue('String', (string) $this->selectionVariant)),
            new PropertyValue('PresentationVariant', new ConstantAnnotationValue('String', (string) $this->presentationVariant)),
        );
    }
}
