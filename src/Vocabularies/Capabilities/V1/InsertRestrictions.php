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
 * Restrictions on insert operations
 * @see TypedAnnotationInterface
 */
final readonly class InsertRestrictions implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.InsertRestrictions';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
    ];

    public function __construct(
        public readonly array $nonInsertableProperties,
        public readonly array $nonInsertableNavigationProperties,
        public readonly array $requiredProperties,
        public readonly ?array $permissions = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Capabilities.InsertRestrictionsType',
            new PropertyValue('NonInsertableProperties', new ConstantAnnotationValue('String', (string) $this->nonInsertableProperties)),
            new PropertyValue('NonInsertableNavigationProperties', new ConstantAnnotationValue('String', (string) $this->nonInsertableNavigationProperties)),
            new PropertyValue('RequiredProperties', new ConstantAnnotationValue('String', (string) $this->requiredProperties)),
            new PropertyValue('Permissions', new ConstantAnnotationValue('String', (string) $this->permissions)),
        );
    }
}
