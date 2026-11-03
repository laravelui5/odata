<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;

/**
 * Change tracking capabilities of this service or entity set
 * @see TypedAnnotationInterface
 */
final readonly class ChangeTracking implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.ChangeTracking';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        SingletonInterface::class,
        FunctionInterface::class,
        FunctionImportInterface::class,
        NavigationPropertyInterface::class,
    ];

    /**
     * @param bool|Path|null $supported odata.track-changes preference is supported
     * @param list<string>|Path|null $filterableProperties Change tracking supports filters on these properties
     * @param list<string>|Path|null $expandableProperties Change tracking supports these properties expanded
     */
    public function __construct(
        public bool|Path|null $supported = null,
        public array|Path|null $filterableProperties = null,
        public array|Path|null $expandableProperties = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new ChangeTrackingType(
            supported: $this->supported,
            filterableProperties: $this->filterableProperties,
            expandableProperties: $this->expandableProperties,
        ))->toRecord();
    }
}
