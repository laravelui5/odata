<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Group of semantically connected fields with a representation template and an optional label
 */
final readonly class ConnectedFieldsType
{
    public function __construct(
        public readonly string $template,
        public readonly mixed $data,
        public readonly ?string $label = null,
    ) {}
}
