<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

final readonly class DraftNodeType
{
    public function __construct(
        public readonly ?string $preparationAction = null,
        public readonly ?string $validationFunction = null,
    ) {}
}
