<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * ...
 * @see TypedAnnotationInterface
 */
final readonly class Application implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.Application';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [];

    public function __construct(
        public readonly ?string $component = null,
        public readonly ?string $serviceRepository = null,
        public readonly ?string $serviceId = null,
        public readonly ?string $serviceVersion = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Common.ApplicationType',
            new PropertyValue('Component', new ConstantAnnotationValue('String', (string) $this->component)),
            new PropertyValue('ServiceRepository', new ConstantAnnotationValue('String', (string) $this->serviceRepository)),
            new PropertyValue('ServiceId', new ConstantAnnotationValue('String', (string) $this->serviceId)),
            new PropertyValue('ServiceVersion', new ConstantAnnotationValue('String', (string) $this->serviceVersion)),
        );
    }
}
