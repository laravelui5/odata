<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ErrorResolutionType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.ErrorResolutionType';

    public const array FIELDS = [
        'Analysis' => ['analysis', 'Edm.String', false],
        'Note' => ['note', 'Edm.String', false],
        'AdditionalNote' => ['additionalNote', 'Edm.String', false],
    ];

    public function __construct(
        public string|Path|null $analysis = null,
        public string|Path|null $note = null,
        public string|Path|null $additionalNote = null,
    ) {}
}
