<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Restrictions for retrieving a collection of entities, retrieving a singleton instance.
 * @see TypedAnnotationInterface
 */
final readonly class ReadRestrictions implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.ReadRestrictions';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        SingletonInterface::class,
    ];

    public function __construct(
        public readonly bool $typecastSegmentSupported,
        public readonly mixed $readByKeyRestrictions = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Capabilities.ReadRestrictionsType',
            new PropertyValue('TypecastSegmentSupported', new ConstantAnnotationValue('Boolean', (string) $this->typecastSegmentSupported)),
            new PropertyValue('ReadByKeyRestrictions', new ConstantAnnotationValue('String', (string) $this->readByKeyRestrictions)),
        );
    }
}
