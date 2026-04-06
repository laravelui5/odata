<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Describes side-effects of modification operations
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class SideEffects implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.SideEffects';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        EntityTypeInterface::class,
        ComplexTypeInterface::class,
        FunctionInterface::class,
    ];

    public function __construct(
        public readonly array $sourceProperties,
        public readonly array $sourceEvents,
        public readonly array $targetProperties,
        public readonly bool $discretionary,
        public readonly ?array $sourceEntities = null,
        public readonly ?array $targetEntities = null,
        public readonly ?string $triggerAction = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Common.SideEffectsType',
            new PropertyValue('SourceProperties', new ConstantAnnotationValue('String', (string) $this->sourceProperties)),
            new PropertyValue('SourceEntities', new ConstantAnnotationValue('String', (string) $this->sourceEntities)),
            new PropertyValue('SourceEvents', new ConstantAnnotationValue('String', (string) $this->sourceEvents)),
            new PropertyValue('TargetProperties', new ConstantAnnotationValue('String', (string) $this->targetProperties)),
            new PropertyValue('TargetEntities', new ConstantAnnotationValue('String', (string) $this->targetEntities)),
            new PropertyValue('TriggerAction', new ConstantAnnotationValue('String', (string) $this->triggerAction)),
            new PropertyValue('Discretionary', new ConstantAnnotationValue('Boolean', (string) $this->discretionary)),
        );
    }
}
