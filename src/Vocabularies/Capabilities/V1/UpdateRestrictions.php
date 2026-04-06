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
 * Restrictions on update operations
 * @see TypedAnnotationInterface
 */
final readonly class UpdateRestrictions implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.UpdateRestrictions';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        SingletonInterface::class,
    ];

    public function __construct(
        public readonly array $nonUpdatableProperties,
        public readonly array $nonUpdatableNavigationProperties,
        public readonly array $requiredProperties,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Capabilities.UpdateRestrictionsType',
            new PropertyValue('NonUpdatableProperties', new ConstantAnnotationValue('String', (string) $this->nonUpdatableProperties)),
            new PropertyValue('NonUpdatableNavigationProperties', new ConstantAnnotationValue('String', (string) $this->nonUpdatableNavigationProperties)),
            new PropertyValue('RequiredProperties', new ConstantAnnotationValue('String', (string) $this->requiredProperties)),
        );
    }
}
