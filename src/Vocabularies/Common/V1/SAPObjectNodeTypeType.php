<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

/**
 * Information about an SAP Object Node Type
 */
final readonly class SAPObjectNodeTypeType
{
    public function __construct(
        public readonly string $name,
    ) {}
}
