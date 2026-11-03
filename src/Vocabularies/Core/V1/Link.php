<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * The Link type is inspired by the `atom:link` element, see [RFC4287](https://tools.ietf.org/html/rfc4287#section-4.2.7), and the `Link` HTTP header, see [RFC5988](https://tools.ietf.org/html/rfc5988)
 */
final readonly class Link extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Core.V1.Link';

    public const array FIELDS = [
        'rel' => ['rel', 'Edm.String', true],
        'href' => ['href', 'Edm.String', true],
    ];

    public function __construct(
        public string|Path|null $rel = null,
        public string|Path|null $href = null,
    ) {}
}
