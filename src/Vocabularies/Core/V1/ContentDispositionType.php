<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ContentDispositionType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Core.V1.ContentDispositionType';

    public const array FIELDS = [
        'Type' => ['type', 'Edm.String', false],
        'Filename' => ['filename', 'Edm.String', false],
    ];

    /**
     * @param string|Path|null $type The disposition type of the binary or stream value, see [RFC 6266, Disposition Type](https://datatracker.ietf.org/doc/html/rfc6266#section-4.2)
     * @param string|Path|null $filename The proposed filename for downloading the binary or stream value, see [RFC 6266, Disposition Parameter: 'Filename'](https://datatracker.ietf.org/doc/html/rfc6266#section-4.3)
     */
    public function __construct(
        public string|Path|null $type = null,
        public string|Path|null $filename = null,
    ) {}
}
