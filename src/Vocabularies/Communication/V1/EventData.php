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
     * @param string|Path|null $summary Short description of the event
     * @param string|Path|null $description More complete description
     * @param list<string>|Path|null $categories Categories or subtypes of the event
     * @param string|Path|null $dtstart Start date and time of the event
     * @param string|Path|null $dtend Date and time by which the event ends, alternative to duration
     * @param string|Path|null $duration Duration of the event, alternative to dtend
     * @param string|Path|null $class Access classification, e.g. PUBLIC, PRIVATE, CONFIDENTIAL
     * @param string|Path|null $status Confirmation status, e.g. CONFIRMED, TENTATIVE, CANCELLED
     * @param string|Path|null $location Intended venue of the event
     * @param bool|Path|null $transp Time transparency for busy time searches, true = free, false = blocked
     * @param bool|Path|null $wholeday Wholeday event
     * @param string|Path|null $fbtype Free or busy time type, e.g. FREE, BUSY, BUSY-TENTATIVE
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
