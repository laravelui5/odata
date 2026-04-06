<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Task list entry
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Task implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Communication.v1.Task';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

    public function __construct(
        public readonly ?string $summary = null,
        public readonly ?string $description = null,
        public readonly ?string $due = null,
        public readonly ?string $completed = null,
        public readonly ?int $percentcomplete = null,
        public readonly ?int $priority = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Communication.TaskData',
            new PropertyValue('summary', new ConstantAnnotationValue('String', (string) $this->summary)),
            new PropertyValue('description', new ConstantAnnotationValue('String', (string) $this->description)),
            new PropertyValue('due', new ConstantAnnotationValue('DateTimeOffset', (string) $this->due)),
            new PropertyValue('completed', new ConstantAnnotationValue('DateTimeOffset', (string) $this->completed)),
            new PropertyValue('percentcomplete', new ConstantAnnotationValue('Integer', (string) $this->percentcomplete)),
            new PropertyValue('priority', new ConstantAnnotationValue('Integer', (string) $this->priority)),
        );
    }
}
