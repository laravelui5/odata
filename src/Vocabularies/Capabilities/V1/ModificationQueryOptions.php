<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Support for query options with modification requests (insert, update, action invocation)
 * @see TypedAnnotationInterface
 */
final readonly class ModificationQueryOptions implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.ModificationQueryOptions';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityContainerInterface::class,
        FunctionInterface::class,
    ];

    public function __construct(
        public readonly bool $expandSupported,
        public readonly bool $selectSupported,
        public readonly bool $computeSupported,
        public readonly bool $filterSupported,
        public readonly bool $searchSupported,
        public readonly bool $sortSupported,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Capabilities.ModificationQueryOptionsType',
            new PropertyValue('ExpandSupported', new ConstantAnnotationValue('Boolean', (string) $this->expandSupported)),
            new PropertyValue('SelectSupported', new ConstantAnnotationValue('Boolean', (string) $this->selectSupported)),
            new PropertyValue('ComputeSupported', new ConstantAnnotationValue('Boolean', (string) $this->computeSupported)),
            new PropertyValue('FilterSupported', new ConstantAnnotationValue('Boolean', (string) $this->filterSupported)),
            new PropertyValue('SearchSupported', new ConstantAnnotationValue('Boolean', (string) $this->searchSupported)),
            new PropertyValue('SortSupported', new ConstantAnnotationValue('Boolean', (string) $this->sortSupported)),
        );
    }
}
