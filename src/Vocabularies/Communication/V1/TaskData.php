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

    /**
     * @param string|Path|null $summary Short description of the task
     * @param string|Path|null $description More complete description of the task
     * @param string|Path|null $due Date and time that a to-do is expected to be completed
     * @param string|Path|null $completed Date and time that a to-do was actually completed
     * @param int|Path|null $percentcomplete Percent completion of a to-do, e.g. 50 for half done
     * @param int|Path|null $priority Relative priority, 0 = undefined, 1 = highest, 9 = lowest
     */
    public function __construct(
        public string|Path|null $summary = null,
        public string|Path|null $description = null,
        public string|Path|null $due = null,
        public string|Path|null $completed = null,
        public int|Path|null $percentcomplete = null,
        public int|Path|null $priority = null,
    ) {}
}
