<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Deep Update Support of the annotated resource (the whole service, an entity set, or a collection-valued resource)
 * @see TypedAnnotationInterface
 */
final readonly class DeepUpdateSupport implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.DeepUpdateSupport';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityContainerInterface::class,
        EntitySetInterface::class,
    ];

    public function __construct(
        public readonly bool $supported,
        public readonly bool $contentIDSupported,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Capabilities.DeepUpdateSupportType',
            new PropertyValue('Supported', new ConstantAnnotationValue('Boolean', (string) $this->supported)),
            new PropertyValue('ContentIDSupported', new ConstantAnnotationValue('Boolean', (string) $this->contentIDSupported)),
        );
    }
}
