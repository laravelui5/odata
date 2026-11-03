<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class HeaderInfoType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.HeaderInfoType';

    public const array FIELDS = [
        'TypeName' => ['typeName', 'Edm.String', true],
        'TypeNamePlural' => ['typeNamePlural', 'Edm.String', true],
        'Title' => ['title', 'com.sap.vocabularies.UI.v1.DataFieldAbstract', false],
        'Description' => ['description', 'com.sap.vocabularies.UI.v1.DataFieldAbstract', false],
        'Image' => ['image', 'Edm.Stream', false],
        'ImageUrl' => ['imageUrl', 'Edm.String', false],
        'TypeImageUrl' => ['typeImageUrl', 'Edm.String', false],
        'Initials' => ['initials', 'Edm.String', false],
    ];

    public function __construct(
        public string|Path|null $typeName = null,
        public string|Path|null $typeNamePlural = null,
        public DataFieldAbstract|Path|null $title = null,
        public DataFieldAbstract|Path|null $description = null,
        public mixed $image = null,
        public string|Path|null $imageUrl = null,
        public string|Path|null $typeImageUrl = null,
        public string|Path|null $initials = null,
    ) {}
}
