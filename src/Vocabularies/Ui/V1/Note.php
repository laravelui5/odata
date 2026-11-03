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

    /**
     * @param string|Path|null $title Title of the note
     * @param string|Path|null $content Content of the note, as a string
     * @param string|Path|null $type A type used for grouping notes
     * @param int|Path|null $maximalLength Type-specific maximal length of the content of the note
     * @param bool|Path|null $multipleNotes Whether the type allows multiple notes for one object
     */
    public function __construct(
        public string|Path|null $title = null,
        public string|Path|null $content = null,
        public string|Path|null $type = null,
        public int|Path|null $maximalLength = null,
        public bool|Path|null $multipleNotes = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new NoteType(
            title: $this->title,
            content: $this->content,
            type: $this->type,
            maximalLength: $this->maximalLength,
            multipleNotes: $this->multipleNotes,
        ))->toRecord();
    }
}
