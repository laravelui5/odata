<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ResourceExceptionType extends ExceptionType
{
    public const string TYPE = 'Org.OData.Core.V1.ResourceExceptionType';

    public const array FIELDS = [
        'info' => ['info', 'Org.OData.Core.V1.MessageType', false],
        'retryLink' => ['retryLink', 'Edm.String', false],
    ];

    /**
     * @param MessageType|Path|null $info Information about the exception
     * @param string|Path|null $retryLink A GET request to this URL retries retrieving the problematic instance
     */
    public function __construct(
        MessageType|Path|null $info = null,
        public string|Path|null $retryLink = null,
    ) {
        parent::__construct(
            info: $info,
        );
    }
}
