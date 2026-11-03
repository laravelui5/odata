<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * A non-empty collection lists the full set of supported protocols. A empty collection means 'only HTTP is supported'
 */
final readonly class CallbackType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.CallbackType';

    public const array FIELDS = [
        'CallbackProtocols' => ['callbackProtocols', 'Collection(Org.OData.Capabilities.V1.CallbackProtocol)', false],
    ];

    /**
     * @param list<CallbackProtocol>|Path|null $callbackProtocols List of supported callback protocols, e.g. `http` or `wss`
     */
    public function __construct(
        public array|Path|null $callbackProtocols = null,
    ) {}
}
