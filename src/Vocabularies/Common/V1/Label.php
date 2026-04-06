<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * A short, human-readable text suitable for labels and captions in UIs
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_ALL)]
final readonly class Label implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.Label';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [];

    public function __construct(
        public readonly string $value,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new ConstantAnnotationValue('String', (string) $this->value);
    }
}
