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
     * @param string|Path|null $from Author(s) of the message
     * @param string|Path|null $sender Agent responsible for the actual transmission of the message, e.g. a secretary
     * @param list<string>|Path|null $to List of primary recipients
     * @param list<string>|Path|null $cc List of other recipients (carbon copy)
     * @param list<string>|Path|null $bcc List of recipients whose addresses are not to be revealed (blind carbon copy)
     * @param string|Path|null $subject Topic of the message
     * @param string|Path|null $body Main part of the message
     * @param list<string>|Path|null $keywords List of important words and phrases that might be useful for the recipient
     * @param string|Path|null $received Date and time the message was received
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
