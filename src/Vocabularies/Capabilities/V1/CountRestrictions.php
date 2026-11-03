<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;

/**
 * Restrictions on /$count path suffix and $count=true system query option
 * @see TypedAnnotationInterface
 */
final readonly class CountRestrictions implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.CountRestrictions';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        PropertyInterface::class,
        NavigationPropertyInterface::class,
    ];

    /**
     * @param list<string>|Path|null $nonCountableProperties
     * @param list<string>|Path|null $nonCountableNavigationProperties
     */
    public function __construct(
        public bool|Path|null $countable = null,
        public array|Path|null $nonCountableProperties = null,
        public array|Path|null $nonCountableNavigationProperties = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new CountRestrictionsType(
            countable: $this->countable,
            nonCountableProperties: $this->nonCountableProperties,
            nonCountableNavigationProperties: $this->nonCountableNavigationProperties,
        ))->toRecord();
    }
}
