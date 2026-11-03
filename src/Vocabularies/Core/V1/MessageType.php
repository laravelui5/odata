<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class MessageType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Core.V1.MessageType';

    public const array FIELDS = [
        'code' => ['code', 'Edm.String', true],
        'message' => ['message', 'Edm.String', true],
        'severity' => ['severity', 'Edm.String', true],
        'target' => ['target', 'Edm.String', false],
        'details' => ['details', 'Collection(Org.OData.Core.V1.MessageType)', false],
    ];

    /**
     * @param list<MessageType>|Path|null $details
     */
    public function __construct(
        public string|Path|null $code = null,
        public string|Path|null $message = null,
        public string|Path|null $severity = null,
        public string|Path|null $target = null,
        public array|Path|null $details = null,
    ) {}
}
