<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

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

    /**
     * @param string|Path|null $url URL of media resource
     * @param mixed $stream Stream of media resource
     * @param string|Path|null $contentType Content type, such as application/pdf, video/x-flv, image/jpeg
     * @param int|Path|null $byteSize Resource size in bytes
     * @param string|Path|null $changedAt Date of last change
     * @param ImageType|Path|null $thumbnail Thumbnail image
     * @param DataField|Path|null $title Resource title
     * @param DataField|Path|null $description Resource description
     */
    public function __construct(
        public string|Path|null $url = null,
        public mixed $stream = null,
        public string|Path|null $contentType = null,
        public int|Path|null $byteSize = null,
        public string|Path|null $changedAt = null,
        public ImageType|Path|null $thumbnail = null,
        public DataField|Path|null $title = null,
        public DataField|Path|null $description = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new MediaResourceType(
            url: $this->url,
            stream: $this->stream,
            contentType: $this->contentType,
            byteSize: $this->byteSize,
            changedAt: $this->changedAt,
            thumbnail: $this->thumbnail,
            title: $this->title,
            description: $this->description,
        ))->toRecord();
    }
}
