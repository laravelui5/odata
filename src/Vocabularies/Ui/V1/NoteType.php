<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class NoteType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.NoteType';

    public const array FIELDS = [
        'Title' => ['title', 'Edm.String', false],
        'Content' => ['content', 'Edm.String', true],
        'Type' => ['type', 'Edm.String', true],
        'MaximalLength' => ['maximalLength', 'Edm.Int32', false],
        'MultipleNotes' => ['multipleNotes', 'Edm.Boolean', false],
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
    ) {}
}
