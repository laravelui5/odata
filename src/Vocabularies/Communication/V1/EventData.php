<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class EventData extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Communication.v1.EventData';

    public const array FIELDS = [
        'summary' => ['summary', 'Edm.String', false],
        'description' => ['description', 'Edm.String', false],
        'categories' => ['categories', 'Collection(Edm.String)', false],
        'dtstart' => ['dtstart', 'Edm.DateTimeOffset', false],
        'dtend' => ['dtend', 'Edm.DateTimeOffset', false],
        'duration' => ['duration', 'Edm.Duration', false],
        'class' => ['class', 'Edm.String', false],
        'status' => ['status', 'Edm.String', false],
        'location' => ['location', 'Edm.String', false],
        'transp' => ['transp', 'Edm.Boolean', false],
        'wholeday' => ['wholeday', 'Edm.Boolean', false],
        'fbtype' => ['fbtype', 'Edm.String', false],
    ];

    /**
     * @param list<string>|Path|null $categories
     */
    public function __construct(
        public string|Path|null $summary = null,
        public string|Path|null $description = null,
        public array|Path|null $categories = null,
        public string|Path|null $dtstart = null,
        public string|Path|null $dtend = null,
        public string|Path|null $duration = null,
        public string|Path|null $class = null,
        public string|Path|null $status = null,
        public string|Path|null $location = null,
        public bool|Path|null $transp = null,
        public bool|Path|null $wholeday = null,
        public string|Path|null $fbtype = null,
    ) {}
}
