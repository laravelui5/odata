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

    /**
     * @param string|Path|null $id Optional identifier to reference this variant from an external context
     * @param string|Path|null $text Name of the bundling variant
     * @param SelectionVariantType|Path|null $selectionVariant Selection variant, either specified inline or referencing another annotation via Path
     * @param PresentationVariantType|Path|null $presentationVariant Presentation variant, either specified inline or referencing another annotation via Path
     */
    public function __construct(
        public string|Path|null $id = null,
        public string|Path|null $text = null,
        public SelectionVariantType|Path|null $selectionVariant = null,
        public PresentationVariantType|Path|null $presentationVariant = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): AnnotationValueInterface
    {
        return (new SelectionPresentationVariantType(
            id: $this->id,
            text: $this->text,
            selectionVariant: $this->selectionVariant,
            presentationVariant: $this->presentationVariant,
        ))->toRecord();
    }
}
