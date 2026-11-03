<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\AnnotationValueWriter;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\SchemaInterface;
use LogicException;

/**
 * 1 for first version of a service, incremented when schema changes incompatibly and service is published with a different URI
 * @see TypedAnnotationInterface
 */
final readonly class ServiceVersion implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.ServiceVersion';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        SchemaInterface::class,
    ];

    public function __construct(
        public int|Path|null $value = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        if ($this->value === null) {
            throw new LogicException('The term com.sap.vocabularies.Common.v1.ServiceVersion requires a value.');
        }
        return AnnotationValueWriter::write($this->value, 'Edm.Int32');
    }
}
