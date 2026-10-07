<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

/**
 * The resource path and the raw query string of a request, as the client sent them —
 * what a next link has to repeat.
 *
 * A next link stands for the rest of *the same* collection query: same path, same
 * `$filter`, `$orderby`, `$select`, `$expand`, `$search`, `$count`, same custom
 * query options. Only `$skip` moves. The other parameters are carried over byte for
 * byte, so nothing is re-encoded on the way.
 */
final readonly class RequestTarget
{
    /**
     * @param string $path        the resource path relative to the service root (`/Products(1)/Orders`)
     * @param string $queryString the raw query string, without `?`
     */
    public function __construct(
        public string $path,
        public string $queryString = '',
    ) {}

    public function nextLink(string $serviceRoot, int $skip): string
    {
        $pairs = array_filter(
            $this->queryString === '' ? [] : explode('&', $this->queryString),
            static fn (string $pair): bool => $pair !== '' && rawurldecode(explode('=', $pair, 2)[0]) !== '$skip',
        );
        $pairs[] = '$skip=' . $skip;

        return $serviceRoot . ltrim($this->path, '/') . '?' . implode('&', $pairs);
    }
}
