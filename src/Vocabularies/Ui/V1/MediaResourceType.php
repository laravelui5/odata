<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class MediaResourceType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.MediaResourceType';

    public const array FIELDS = [
        'Url' => ['url', 'Edm.String', false],
        'Stream' => ['stream', 'Edm.Stream', false],
        'ContentType' => ['contentType', 'Edm.String', false],
        'ByteSize' => ['byteSize', 'Edm.Int64', false],
        'ChangedAt' => ['changedAt', 'Edm.DateTimeOffset', false],
        'Thumbnail' => ['thumbnail', 'com.sap.vocabularies.UI.v1.ImageType', false],
        'Title' => ['title', 'com.sap.vocabularies.UI.v1.DataField', false],
        'Description' => ['description', 'com.sap.vocabularies.UI.v1.DataField', false],
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
    ) {}
}
