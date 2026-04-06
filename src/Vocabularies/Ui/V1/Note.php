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
 * Visualization of a note attached to an entity
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Note implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.Note';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

    public function __construct(
        public readonly string $content,
        public readonly string $type,
        public readonly bool $multipleNotes,
        public readonly ?string $title = null,
        public readonly ?int $maximalLength = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'UI.NoteType',
            new PropertyValue('Title', new ConstantAnnotationValue('String', (string) $this->title)),
            new PropertyValue('Content', new ConstantAnnotationValue('String', (string) $this->content)),
            new PropertyValue('Type', new ConstantAnnotationValue('String', (string) $this->type)),
            new PropertyValue('MaximalLength', new ConstantAnnotationValue('Integer', (string) $this->maximalLength)),
            new PropertyValue('MultipleNotes', new ConstantAnnotationValue('Boolean', (string) $this->multipleNotes)),
        );
    }
}
