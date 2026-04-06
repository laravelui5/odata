<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Support for $select and nested query options within $select
 * @see TypedAnnotationInterface
 */
final readonly class SelectSupport implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.SelectSupport';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityContainerInterface::class,
        EntitySetInterface::class,
        SingletonInterface::class,
    ];

    public function __construct(
        public readonly bool $supported,
        public readonly bool $instanceAnnotationsSupported,
        public readonly bool $expandable,
        public readonly bool $filterable,
        public readonly bool $searchable,
        public readonly bool $topSupported,
        public readonly bool $skipSupported,
        public readonly bool $computeSupported,
        public readonly bool $countable,
        public readonly bool $sortable,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Capabilities.SelectSupportType',
            new PropertyValue('Supported', new ConstantAnnotationValue('Boolean', (string) $this->supported)),
            new PropertyValue('InstanceAnnotationsSupported', new ConstantAnnotationValue('Boolean', (string) $this->instanceAnnotationsSupported)),
            new PropertyValue('Expandable', new ConstantAnnotationValue('Boolean', (string) $this->expandable)),
            new PropertyValue('Filterable', new ConstantAnnotationValue('Boolean', (string) $this->filterable)),
            new PropertyValue('Searchable', new ConstantAnnotationValue('Boolean', (string) $this->searchable)),
            new PropertyValue('TopSupported', new ConstantAnnotationValue('Boolean', (string) $this->topSupported)),
            new PropertyValue('SkipSupported', new ConstantAnnotationValue('Boolean', (string) $this->skipSupported)),
            new PropertyValue('ComputeSupported', new ConstantAnnotationValue('Boolean', (string) $this->computeSupported)),
            new PropertyValue('Countable', new ConstantAnnotationValue('Boolean', (string) $this->countable)),
            new PropertyValue('Sortable', new ConstantAnnotationValue('Boolean', (string) $this->sortable)),
        );
    }
}
