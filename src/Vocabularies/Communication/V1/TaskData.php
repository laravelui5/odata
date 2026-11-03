<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class TaskData extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Communication.v1.TaskData';

    public const array FIELDS = [
        'summary' => ['summary', 'Edm.String', false],
        'description' => ['description', 'Edm.String', false],
        'due' => ['due', 'Edm.DateTimeOffset', false],
        'completed' => ['completed', 'Edm.DateTimeOffset', false],
        'percentcomplete' => ['percentcomplete', 'Edm.Byte', false],
        'priority' => ['priority', 'Edm.Byte', false],
    ];

    public function __construct(
        public string|Path|null $summary = null,
        public string|Path|null $description = null,
        public string|Path|null $due = null,
        public string|Path|null $completed = null,
        public int|Path|null $percentcomplete = null,
        public int|Path|null $priority = null,
    ) {}
}
