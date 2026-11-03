<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Properties that describe an image
 */
final readonly class ImageType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.ImageType';

    public const array FIELDS = [
        'Url' => ['url', 'Edm.String', true],
        'Stream' => ['stream', 'Edm.Stream', false],
        'Width' => ['width', 'Edm.String', false],
        'Height' => ['height', 'Edm.String', false],
    ];

    public function __construct(
        public string|Path|null $url = null,
        public mixed $stream = null,
        public string|Path|null $width = null,
        public string|Path|null $height = null,
    ) {}
}
