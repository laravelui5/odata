<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class RevisionType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Core.V1.RevisionType';

    public const array FIELDS = [
        'Version' => ['version', 'Edm.String', false],
        'Kind' => ['kind', 'Org.OData.Core.V1.RevisionKind', true],
        'Description' => ['description', 'Edm.String', true],
    ];

    /**
     * @param string|Path|null $version The schema version with which this revision was first published
     * @param RevisionKind|Path|null $kind The kind of revision
     * @param string|Path|null $description Text describing the reason for the revision
     */
    public function __construct(
        public string|Path|null $version = null,
        public RevisionKind|Path|null $kind = null,
        public string|Path|null $description = null,
    ) {}
}
