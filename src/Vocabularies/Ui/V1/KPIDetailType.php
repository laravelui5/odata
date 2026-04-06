<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

final readonly class KPIDetailType
{
    public function __construct(
        public readonly array $alternativePresentationVariants,
        public readonly mixed $defaultPresentationVariant = null,
        public readonly ?string $semanticObject = null,
        public readonly ?string $action = null,
    ) {}
}
