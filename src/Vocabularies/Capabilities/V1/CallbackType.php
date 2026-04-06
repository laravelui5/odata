<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

/**
 * A non-empty collection lists the full set of supported protocols. A empty collection means 'only HTTP is supported'
 */
final readonly class CallbackType
{
    public function __construct(
        public readonly array $callbackProtocols,
    ) {}
}
