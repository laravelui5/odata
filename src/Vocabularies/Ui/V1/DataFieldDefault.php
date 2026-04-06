<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Default representation of a property as a datafield, e.g. when the property is added as a table column or form field via personalization
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class DataFieldDefault implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.DataFieldDefault';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        PropertyInterface::class,
    ];

    public function __construct(
        public readonly ?string $label = null,
        public readonly mixed $criticality = null,
        public readonly mixed $criticalityRepresentation = null,
        public readonly ?string $iconUrl = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'UI.DataFieldAbstract',
            new PropertyValue('Label', new ConstantAnnotationValue('String', (string) $this->label)),
            new PropertyValue('Criticality', new ConstantAnnotationValue('String', (string) $this->criticality)),
            new PropertyValue('CriticalityRepresentation', new ConstantAnnotationValue('String', (string) $this->criticalityRepresentation)),
            new PropertyValue('IconUrl', new ConstantAnnotationValue('String', (string) $this->iconUrl)),
        );
    }
}
