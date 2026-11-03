<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

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

    /**
     * @param string|Path|null $summary Short description of the task
     * @param string|Path|null $description More complete description of the task
     * @param string|Path|null $due Date and time that a to-do is expected to be completed
     * @param string|Path|null $completed Date and time that a to-do was actually completed
     * @param int|Path|null $percentcomplete Percent completion of a to-do, e.g. 50 for half done
     * @param int|Path|null $priority Relative priority, 0 = undefined, 1 = highest, 9 = lowest
     */
    public function __construct(
        public string|Path|null $summary = null,
        public string|Path|null $description = null,
        public string|Path|null $due = null,
        public string|Path|null $completed = null,
        public int|Path|null $percentcomplete = null,
        public int|Path|null $priority = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new TaskData(
            summary: $this->summary,
            description: $this->description,
            due: $this->due,
            completed: $this->completed,
            percentcomplete: $this->percentcomplete,
            priority: $this->priority,
        ))->toRecord();
    }
}
