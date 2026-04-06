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
 * Defines a recursive hierarchy.
 * @see TypedAnnotationInterface
 */
final readonly class RecursiveHierarchy implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.RecursiveHierarchy';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [];

    public function __construct(
        public readonly ?string $externalNodeKeyProperty = null,
        public readonly ?string $nodeDescendantCountProperty = null,
        public readonly ?string $nodeDrillStateProperty = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Common.RecursiveHierarchyType',
            new PropertyValue('ExternalNodeKeyProperty', new ConstantAnnotationValue('PropertyPath', (string) $this->externalNodeKeyProperty)),
            new PropertyValue('NodeDescendantCountProperty', new ConstantAnnotationValue('PropertyPath', (string) $this->nodeDescendantCountProperty)),
            new PropertyValue('NodeDrillStateProperty', new ConstantAnnotationValue('PropertyPath', (string) $this->nodeDrillStateProperty)),
        );
    }
}
