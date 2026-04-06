<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * A [Feature Object](https://datatracker.ietf.org/doc/html/rfc7946#section-3.2) represents a spatially bounded thing
 * @see TypedAnnotationInterface
 */
final readonly class GeometryFeature implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Core.V1.GeometryFeature';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [];

    public function __construct(
        public readonly mixed $geometry = null,
        public readonly mixed $properties = null,
        public readonly ?string $id = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Core.GeometryFeatureType',
            new PropertyValue('geometry', new ConstantAnnotationValue('String', (string) $this->geometry)),
            new PropertyValue('properties', new ConstantAnnotationValue('String', (string) $this->properties)),
            new PropertyValue('id', new ConstantAnnotationValue('String', (string) $this->id)),
        );
    }
}
