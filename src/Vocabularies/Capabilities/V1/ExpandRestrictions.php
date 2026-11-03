<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;

/**
 * Restrictions on expand expressions
 * @see TypedAnnotationInterface
 */
final readonly class ExpandRestrictions implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.ExpandRestrictions';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        SingletonInterface::class,
        PropertyInterface::class,
        NavigationPropertyInterface::class,
    ];

    /**
     * @param list<string>|Path|null $nonExpandableProperties
     * @param list<string>|Path|null $nonExpandableStreamProperties
     */
    public function __construct(
        public bool|Path|null $expandable = null,
        public bool|Path|null $streamsExpandable = null,
        public int|Path|null $maxLevels = null,
        public ExpandByKeyRestrictionsBase|Path|null $expandByKeyRestrictions = null,
        public array|Path|null $nonExpandableProperties = null,
        public array|Path|null $nonExpandableStreamProperties = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new ExpandRestrictionsType(
            expandable: $this->expandable,
            streamsExpandable: $this->streamsExpandable,
            maxLevels: $this->maxLevels,
            expandByKeyRestrictions: $this->expandByKeyRestrictions,
            nonExpandableProperties: $this->nonExpandableProperties,
            nonExpandableStreamProperties: $this->nonExpandableStreamProperties,
        ))->toRecord();
    }
}
