<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class DataModificationExceptionType extends ExceptionType
{
    public const string TYPE = 'Org.OData.Core.V1.DataModificationExceptionType';

    public const array FIELDS = [
        'info' => ['info', 'Org.OData.Core.V1.MessageType', false],
        'failedOperation' => ['failedOperation', 'Org.OData.Core.V1.DataModificationOperationKind', true],
        'responseCode' => ['responseCode', 'Edm.Int16', false],
    ];

    public function __construct(
        MessageType|Path|null $info = null,
        public DataModificationOperationKind|Path|null $failedOperation = null,
        public int|Path|null $responseCode = null,
    ) {
        parent::__construct(
            info: $info,
        );
    }
}
