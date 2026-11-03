<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ValueExceptionType extends ExceptionType
{
    public const string TYPE = 'Org.OData.Core.V1.ValueExceptionType';

    public const array FIELDS = [
        'info' => ['info', 'Org.OData.Core.V1.MessageType', false],
        'value' => ['value', 'Edm.String', false],
    ];

    /**
     * @param MessageType|Path|null $info Information about the exception
     * @param string|Path|null $value String representation of the exact value
     */
    public function __construct(
        MessageType|Path|null $info = null,
        public string|Path|null $value = null,
    ) {
        parent::__construct(
            info: $info,
        );
    }
}
