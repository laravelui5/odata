<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;

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
        PropertyInterface::class,
        NavigationPropertyInterface::class,
    ];

    public function __construct(
        public bool|Path|null $supported = null,
        public bool|Path|null $instanceAnnotationsSupported = null,
        public bool|Path|null $expandable = null,
        public bool|Path|null $filterable = null,
        public bool|Path|null $searchable = null,
        public bool|Path|null $topSupported = null,
        public bool|Path|null $skipSupported = null,
        public bool|Path|null $computeSupported = null,
        public bool|Path|null $countable = null,
        public bool|Path|null $sortable = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new SelectSupportType(
            supported: $this->supported,
            instanceAnnotationsSupported: $this->instanceAnnotationsSupported,
            expandable: $this->expandable,
            filterable: $this->filterable,
            searchable: $this->searchable,
            topSupported: $this->topSupported,
            skipSupported: $this->skipSupported,
            computeSupported: $this->computeSupported,
            countable: $this->countable,
            sortable: $this->sortable,
        ))->toRecord();
    }
}
