<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;

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

    /**
     * @param bool|Path|null $expandSupported Supports $expand with modification requests
     * @param bool|Path|null $selectSupported Supports $select with modification requests
     * @param bool|Path|null $computeSupported Supports $compute with modification requests
     * @param bool|Path|null $filterSupported Supports $filter with modification requests
     * @param bool|Path|null $searchSupported Supports $search with modification requests
     * @param bool|Path|null $sortSupported Supports $orderby with modification requests
     */
    public function __construct(
        public bool|Path|null $expandSupported = null,
        public bool|Path|null $selectSupported = null,
        public bool|Path|null $computeSupported = null,
        public bool|Path|null $filterSupported = null,
        public bool|Path|null $searchSupported = null,
        public bool|Path|null $sortSupported = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): AnnotationValueInterface
    {
        return (new ModificationQueryOptionsType(
            expandSupported: $this->expandSupported,
            selectSupported: $this->selectSupported,
            computeSupported: $this->computeSupported,
            filterSupported: $this->filterSupported,
            searchSupported: $this->searchSupported,
            sortSupported: $this->sortSupported,
        ))->toRecord();
    }
}
