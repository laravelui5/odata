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
 * Hints for resolving this error
 * @see TypedAnnotationInterface
 */
final readonly class ErrorResolution implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.ErrorResolution';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [];

    public function __construct(
        public readonly ?string $analysis = null,
        public readonly ?string $note = null,
        public readonly ?string $additionalNote = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Common.ErrorResolutionType',
            new PropertyValue('Analysis', new ConstantAnnotationValue('String', (string) $this->analysis)),
            new PropertyValue('Note', new ConstantAnnotationValue('String', (string) $this->note)),
            new PropertyValue('AdditionalNote', new ConstantAnnotationValue('String', (string) $this->additionalNote)),
        );
    }
}
