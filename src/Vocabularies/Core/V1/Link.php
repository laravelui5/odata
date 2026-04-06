<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

/**
 * The Link type is inspired by the `atom:link` element, see [RFC4287](https://tools.ietf.org/html/rfc4287#section-4.2.7), and the `Link` HTTP header, see [RFC5988](https://tools.ietf.org/html/rfc5988)
 */
final readonly class Link
{
    public function __construct(
        public readonly string $rel,
        public readonly string $href,
    ) {}
}
