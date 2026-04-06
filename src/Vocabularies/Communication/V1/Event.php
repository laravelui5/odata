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
 * Calendar entry
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Event implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Communication.v1.Event';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

    public function __construct(
        public readonly array $categories,
        public readonly ?string $summary = null,
        public readonly ?string $description = null,
        public readonly ?string $dtstart = null,
        public readonly ?string $dtend = null,
        public readonly ?string $duration = null,
        public readonly ?string $class = null,
        public readonly ?string $status = null,
        public readonly ?string $location = null,
        public readonly ?bool $transp = null,
        public readonly ?bool $wholeday = null,
        public readonly ?string $fbtype = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Communication.EventData',
            new PropertyValue('summary', new ConstantAnnotationValue('String', (string) $this->summary)),
            new PropertyValue('description', new ConstantAnnotationValue('String', (string) $this->description)),
            new PropertyValue('categories', new ConstantAnnotationValue('String', (string) $this->categories)),
            new PropertyValue('dtstart', new ConstantAnnotationValue('DateTimeOffset', (string) $this->dtstart)),
            new PropertyValue('dtend', new ConstantAnnotationValue('DateTimeOffset', (string) $this->dtend)),
            new PropertyValue('duration', new ConstantAnnotationValue('Duration', (string) $this->duration)),
            new PropertyValue('class', new ConstantAnnotationValue('String', (string) $this->class)),
            new PropertyValue('status', new ConstantAnnotationValue('String', (string) $this->status)),
            new PropertyValue('location', new ConstantAnnotationValue('String', (string) $this->location)),
            new PropertyValue('transp', new ConstantAnnotationValue('Boolean', (string) $this->transp)),
            new PropertyValue('wholeday', new ConstantAnnotationValue('Boolean', (string) $this->wholeday)),
            new PropertyValue('fbtype', new ConstantAnnotationValue('String', (string) $this->fbtype)),
        );
    }
}
