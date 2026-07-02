<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Http;

/**
 * Minimal request value object for the QueryPlanner.
 *
 * Holds only the URL path and the system query options that the planner needs.
 * Intentionally independent of Illuminate\Http\Request so that tier-2 tests
 * (QueryPlanner tests) can construct it without booting Laravel.
 *
 * The existing Controller\ODataRequest (legacy) is converted into this type
 * by the HTTP entry point once it is wired in Step 7.
 */
final readonly class ODataRequest
{
    public function __construct(
        public readonly string  $path,
        public readonly ?string $filter    = null,
        public readonly ?string $select    = null,
        public readonly ?string $orderBy   = null,
        public readonly ?int    $top       = null,
        public readonly ?int    $skip      = null,
        public readonly ?string $skipToken = null,
        public readonly ?string $expand    = null,
        public readonly ?string $search    = null,
        public readonly ?string $compute   = null,
        public readonly bool    $count     = false,
        public readonly ?int    $maxPageSize = null,
        public readonly CustomQueryOptions $customQueryOptions = new CustomQueryOptions(),
    ) {}

    /**
     * Returns the URL path split into non-empty segments, with percent-decoded characters.
     *
     * @return list<string>
     */
    public function pathSegments(): array
    {
        return array_values(
            array_filter(
                array_map('rawurldecode', explode('/', ltrim($this->path, '/'))),
                'strlen'
            )
        );
    }
}
