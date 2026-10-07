<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

/**
 * The protocol version this installation speaks — one answer for the `Version`
 * attribute of `$metadata` and the `OData-Version` header of every response.
 *
 * Read from `odata.version` (default `4.0`), so moving to 4.01 is one line of
 * config. A cached `$metadata` keeps the version it was cached with; run
 * `odata:cache` again after changing it.
 */
final class ODataVersion
{
    public static function current(): string
    {
        return (string) config('odata.version', '4.0');
    }
}
