<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

abstract readonly class ExceptionType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Core.V1.ExceptionType';

    public const array FIELDS = [
        'info' => ['info', 'Org.OData.Core.V1.MessageType', false],
    ];

    /**
     * @param MessageType|Path|null $info Information about the exception
     */
    public function __construct(
        public MessageType|Path|null $info = null,
    ) {}
}
