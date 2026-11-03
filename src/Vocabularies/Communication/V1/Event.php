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

    /**
     * @param string|Path|null $summary Short description of the event
     * @param string|Path|null $description More complete description
     * @param list<string>|Path|null $categories Categories or subtypes of the event
     * @param string|Path|null $dtstart Start date and time of the event
     * @param string|Path|null $dtend Date and time by which the event ends, alternative to duration
     * @param string|Path|null $duration Duration of the event, alternative to dtend
     * @param string|Path|null $class Access classification, e.g. PUBLIC, PRIVATE, CONFIDENTIAL
     * @param string|Path|null $status Confirmation status, e.g. CONFIRMED, TENTATIVE, CANCELLED
     * @param string|Path|null $location Intended venue of the event
     * @param bool|Path|null $transp Time transparency for busy time searches, true = free, false = blocked
     * @param bool|Path|null $wholeday Wholeday event
     * @param string|Path|null $fbtype Free or busy time type, e.g. FREE, BUSY, BUSY-TENTATIVE
     */
    public function __construct(
        public string|Path|null $summary = null,
        public string|Path|null $description = null,
        public array|Path|null $categories = null,
        public string|Path|null $dtstart = null,
        public string|Path|null $dtend = null,
        public string|Path|null $duration = null,
        public string|Path|null $class = null,
        public string|Path|null $status = null,
        public string|Path|null $location = null,
        public bool|Path|null $transp = null,
        public bool|Path|null $wholeday = null,
        public string|Path|null $fbtype = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new EventData(
            summary: $this->summary,
            description: $this->description,
            categories: $this->categories,
            dtstart: $this->dtstart,
            dtend: $this->dtend,
            duration: $this->duration,
            class: $this->class,
            status: $this->status,
            location: $this->location,
            transp: $this->transp,
            wholeday: $this->wholeday,
            fbtype: $this->fbtype,
        ))->toRecord();
    }
}
