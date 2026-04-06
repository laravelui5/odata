<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Batch Support for the service
 * @see TypedAnnotationInterface
 */
final readonly class BatchSupport implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.BatchSupport';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityContainerInterface::class,
    ];

    public function __construct(
        public readonly bool $supported,
        public readonly bool $continueOnErrorSupported,
        public readonly bool $referencesInRequestBodiesSupported,
        public readonly bool $referencesAcrossChangeSetsSupported,
        public readonly bool $etagReferencesSupported,
        public readonly bool $requestDependencyConditionsSupported,
        public readonly array $supportedFormats,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Capabilities.BatchSupportType',
            new PropertyValue('Supported', new ConstantAnnotationValue('Boolean', (string) $this->supported)),
            new PropertyValue('ContinueOnErrorSupported', new ConstantAnnotationValue('Boolean', (string) $this->continueOnErrorSupported)),
            new PropertyValue('ReferencesInRequestBodiesSupported', new ConstantAnnotationValue('Boolean', (string) $this->referencesInRequestBodiesSupported)),
            new PropertyValue('ReferencesAcrossChangeSetsSupported', new ConstantAnnotationValue('Boolean', (string) $this->referencesAcrossChangeSetsSupported)),
            new PropertyValue('EtagReferencesSupported', new ConstantAnnotationValue('Boolean', (string) $this->etagReferencesSupported)),
            new PropertyValue('RequestDependencyConditionsSupported', new ConstantAnnotationValue('Boolean', (string) $this->requestDependencyConditionsSupported)),
            new PropertyValue('SupportedFormats', new ConstantAnnotationValue('String', (string) $this->supportedFormats)),
        );
    }
}
