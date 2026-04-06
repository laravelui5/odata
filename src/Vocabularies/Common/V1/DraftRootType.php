<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

final readonly class DraftRootType
{
    public function __construct(
        public readonly string $activationAction,
        public readonly array $additionalNewActions,
        public readonly ?string $preparationAction = null,
        public readonly ?string $discardAction = null,
        public readonly ?string $editAction = null,
        public readonly ?string $resumeAction = null,
        public readonly ?string $newAction = null,
        public readonly ?string $shareAction = null,
    ) {}
}
