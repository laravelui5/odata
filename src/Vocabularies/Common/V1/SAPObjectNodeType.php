<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

/**
 * The SAP Object Node Type represented by the annotated entity type
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class SAPObjectNodeType implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.SAPObjectNodeType';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

    /**
     * @param string|Path|null $name The name of the SAP Object Node Type
     */
    public function __construct(
        public string|Path|null $name = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new SAPObjectNodeTypeType(
            name: $this->name,
        ))->toRecord();
    }
}
