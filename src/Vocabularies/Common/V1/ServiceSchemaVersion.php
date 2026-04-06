<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\SchemaInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * 0 for first schema version within a service version, incremented when schema changes compatibly
 * @see TypedAnnotationInterface
 */
final readonly class ServiceSchemaVersion implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.ServiceSchemaVersion';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        SchemaInterface::class,
    ];

    public function __construct(
        public readonly int $value,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new ConstantAnnotationValue('Integer', (string) $this->value);
    }
}
