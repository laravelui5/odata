<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class MessageData extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Communication.v1.MessageData';

    public const array FIELDS = [
        'from' => ['from', 'Edm.String', false],
        'sender' => ['sender', 'Edm.String', false],
        'to' => ['to', 'Collection(Edm.String)', false],
        'cc' => ['cc', 'Collection(Edm.String)', false],
        'bcc' => ['bcc', 'Collection(Edm.String)', false],
        'subject' => ['subject', 'Edm.String', false],
        'body' => ['body', 'Edm.String', false],
        'keywords' => ['keywords', 'Collection(Edm.String)', false],
        'received' => ['received', 'Edm.DateTimeOffset', false],
    ];

    /**
     * @param list<string>|Path|null $to
     * @param list<string>|Path|null $cc
     * @param list<string>|Path|null $bcc
     * @param list<string>|Path|null $keywords
     */
    public function __construct(
        public string|Path|null $from = null,
        public string|Path|null $sender = null,
        public array|Path|null $to = null,
        public array|Path|null $cc = null,
        public array|Path|null $bcc = null,
        public string|Path|null $subject = null,
        public string|Path|null $body = null,
        public array|Path|null $keywords = null,
        public string|Path|null $received = null,
    ) {}
}
