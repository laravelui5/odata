<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;

/**
 * Default capability settings for all collection-valued resources in the container
 * @see TypedAnnotationInterface
 */
final readonly class DefaultCapabilities implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.DefaultCapabilities';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityContainerInterface::class,
    ];

    public function __construct(
        public ChangeTrackingBase|Path|null $changeTracking = null,
        public CountRestrictionsBase|Path|null $countRestrictions = null,
        public bool|Path|null $indexableByKey = null,
        public bool|Path|null $topSupported = null,
        public bool|Path|null $skipSupported = null,
        public bool|Path|null $computeSupported = null,
        public SelectSupportType|Path|null $selectSupport = null,
        public FilterRestrictionsBase|Path|null $filterRestrictions = null,
        public SortRestrictionsBase|Path|null $sortRestrictions = null,
        public ExpandRestrictionsBase|Path|null $expandRestrictions = null,
        public SearchRestrictionsType|Path|null $searchRestrictions = null,
        public InsertRestrictionsBase|Path|null $insertRestrictions = null,
        public UpdateRestrictionsBase|Path|null $updateRestrictions = null,
        public DeleteRestrictionsBase|Path|null $deleteRestrictions = null,
        public OperationRestrictionsType|Path|null $operationRestrictions = null,
        public ReadRestrictionsType|Path|null $readRestrictions = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new DefaultCapabilitiesType(
            changeTracking: $this->changeTracking,
            countRestrictions: $this->countRestrictions,
            indexableByKey: $this->indexableByKey,
            topSupported: $this->topSupported,
            skipSupported: $this->skipSupported,
            computeSupported: $this->computeSupported,
            selectSupport: $this->selectSupport,
            filterRestrictions: $this->filterRestrictions,
            sortRestrictions: $this->sortRestrictions,
            expandRestrictions: $this->expandRestrictions,
            searchRestrictions: $this->searchRestrictions,
            insertRestrictions: $this->insertRestrictions,
            updateRestrictions: $this->updateRestrictions,
            deleteRestrictions: $this->deleteRestrictions,
            operationRestrictions: $this->operationRestrictions,
            readRestrictions: $this->readRestrictions,
        ))->toRecord();
    }
}
