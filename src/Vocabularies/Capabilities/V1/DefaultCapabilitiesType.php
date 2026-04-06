<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

final readonly class DefaultCapabilitiesType
{
    public function __construct(
        public readonly mixed $changeTracking = null,
        public readonly mixed $countRestrictions = null,
        public readonly ?bool $indexableByKey = null,
        public readonly ?bool $topSupported = null,
        public readonly ?bool $skipSupported = null,
        public readonly ?bool $computeSupported = null,
        public readonly mixed $selectSupport = null,
        public readonly mixed $filterRestrictions = null,
        public readonly mixed $sortRestrictions = null,
        public readonly mixed $expandRestrictions = null,
        public readonly mixed $searchRestrictions = null,
        public readonly mixed $insertRestrictions = null,
        public readonly mixed $updateRestrictions = null,
        public readonly mixed $deleteRestrictions = null,
        public readonly mixed $operationRestrictions = null,
        public readonly mixed $readRestrictions = null,
    ) {}
}
