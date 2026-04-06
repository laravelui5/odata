<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class BatchSupportType
{
    public function __construct(
        public readonly bool $supported,
        public readonly bool $continueOnErrorSupported,
        public readonly bool $referencesInRequestBodiesSupported,
        public readonly bool $referencesAcrossChangeSetsSupported,
        public readonly bool $etagReferencesSupported,
        public readonly bool $requestDependencyConditionsSupported,
        public readonly array $supportedFormats,
    ) {}
}
