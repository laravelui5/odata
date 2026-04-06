<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

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
 * Properties that describe a media resource
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class MediaResource implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.MediaResource';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

    public function __construct(
        public readonly ?string $url = null,
        public readonly mixed $stream = null,
        public readonly ?string $contentType = null,
        public readonly ?int $byteSize = null,
        public readonly ?string $changedAt = null,
        public readonly mixed $thumbnail = null,
        public readonly mixed $title = null,
        public readonly mixed $description = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'UI.MediaResourceType',
            new PropertyValue('Url', new ConstantAnnotationValue('String', (string) $this->url)),
            new PropertyValue('Stream', new ConstantAnnotationValue('String', (string) $this->stream)),
            new PropertyValue('ContentType', new ConstantAnnotationValue('String', (string) $this->contentType)),
            new PropertyValue('ByteSize', new ConstantAnnotationValue('Integer', (string) $this->byteSize)),
            new PropertyValue('ChangedAt', new ConstantAnnotationValue('DateTimeOffset', (string) $this->changedAt)),
            new PropertyValue('Thumbnail', new ConstantAnnotationValue('String', (string) $this->thumbnail)),
            new PropertyValue('Title', new ConstantAnnotationValue('String', (string) $this->title)),
            new PropertyValue('Description', new ConstantAnnotationValue('String', (string) $this->description)),
        );
    }
}
