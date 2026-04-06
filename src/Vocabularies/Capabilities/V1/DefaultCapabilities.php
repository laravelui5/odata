<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

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
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Capabilities.DefaultCapabilitiesType',
            new PropertyValue('ChangeTracking', new ConstantAnnotationValue('String', (string) $this->changeTracking)),
            new PropertyValue('CountRestrictions', new ConstantAnnotationValue('String', (string) $this->countRestrictions)),
            new PropertyValue('IndexableByKey', new ConstantAnnotationValue('String', (string) $this->indexableByKey)),
            new PropertyValue('TopSupported', new ConstantAnnotationValue('String', (string) $this->topSupported)),
            new PropertyValue('SkipSupported', new ConstantAnnotationValue('String', (string) $this->skipSupported)),
            new PropertyValue('ComputeSupported', new ConstantAnnotationValue('String', (string) $this->computeSupported)),
            new PropertyValue('SelectSupport', new ConstantAnnotationValue('String', (string) $this->selectSupport)),
            new PropertyValue('FilterRestrictions', new ConstantAnnotationValue('String', (string) $this->filterRestrictions)),
            new PropertyValue('SortRestrictions', new ConstantAnnotationValue('String', (string) $this->sortRestrictions)),
            new PropertyValue('ExpandRestrictions', new ConstantAnnotationValue('String', (string) $this->expandRestrictions)),
            new PropertyValue('SearchRestrictions', new ConstantAnnotationValue('String', (string) $this->searchRestrictions)),
            new PropertyValue('InsertRestrictions', new ConstantAnnotationValue('String', (string) $this->insertRestrictions)),
            new PropertyValue('UpdateRestrictions', new ConstantAnnotationValue('String', (string) $this->updateRestrictions)),
            new PropertyValue('DeleteRestrictions', new ConstantAnnotationValue('String', (string) $this->deleteRestrictions)),
            new PropertyValue('OperationRestrictions', new ConstantAnnotationValue('String', (string) $this->operationRestrictions)),
            new PropertyValue('ReadRestrictions', new ConstantAnnotationValue('String', (string) $this->readRestrictions)),
        );
    }
}
