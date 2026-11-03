<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;

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

    /**
     * @param bool|Path|null $supported Service supports requests to $batch
     * @param bool|Path|null $continueOnErrorSupported Service supports the `continue-on-error` preference
     * @param bool|Path|null $referencesInRequestBodiesSupported Service supports Content-ID referencing in request bodies
     * @param bool|Path|null $referencesAcrossChangeSetsSupported Service supports Content-ID referencing across change sets
     * @param bool|Path|null $etagReferencesSupported Service supports referencing Etags from previous requests
     * @param bool|Path|null $requestDependencyConditionsSupported Service supports the `if` member in JSON batch requests
     * @param list<string>|Path|null $supportedFormats Media types of supported formats for $batch
     */
    public function __construct(
        public bool|Path|null $supported = null,
        public bool|Path|null $continueOnErrorSupported = null,
        public bool|Path|null $referencesInRequestBodiesSupported = null,
        public bool|Path|null $referencesAcrossChangeSetsSupported = null,
        public bool|Path|null $etagReferencesSupported = null,
        public bool|Path|null $requestDependencyConditionsSupported = null,
        public array|Path|null $supportedFormats = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new BatchSupportType(
            supported: $this->supported,
            continueOnErrorSupported: $this->continueOnErrorSupported,
            referencesInRequestBodiesSupported: $this->referencesInRequestBodiesSupported,
            referencesAcrossChangeSetsSupported: $this->referencesAcrossChangeSetsSupported,
            etagReferencesSupported: $this->etagReferencesSupported,
            requestDependencyConditionsSupported: $this->requestDependencyConditionsSupported,
            supportedFormats: $this->supportedFormats,
        ))->toRecord();
    }
}
