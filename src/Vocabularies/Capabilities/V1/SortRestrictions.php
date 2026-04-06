<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Restrictions on orderby expressions
 * @see TypedAnnotationInterface
 */
final readonly class SortRestrictions implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.SortRestrictions';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
    ];

    public function __construct(
        public readonly array $ascendingOnlyProperties,
        public readonly array $descendingOnlyProperties,
        public readonly array $nonSortableProperties,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Capabilities.SortRestrictionsType',
            new PropertyValue('AscendingOnlyProperties', new ConstantAnnotationValue('String', (string) $this->ascendingOnlyProperties)),
            new PropertyValue('DescendingOnlyProperties', new ConstantAnnotationValue('String', (string) $this->descendingOnlyProperties)),
            new PropertyValue('NonSortableProperties', new ConstantAnnotationValue('String', (string) $this->nonSortableProperties)),
        );
    }
}
