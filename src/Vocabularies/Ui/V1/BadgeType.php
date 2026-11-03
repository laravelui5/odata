<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class BadgeType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.BadgeType';

    public const array FIELDS = [
        'HeadLine' => ['headLine', 'com.sap.vocabularies.UI.v1.DataField', true],
        'Title' => ['title', 'com.sap.vocabularies.UI.v1.DataField', true],
        'ImageUrl' => ['imageUrl', 'Edm.String', false],
        'TypeImageUrl' => ['typeImageUrl', 'Edm.String', false],
        'MainInfo' => ['mainInfo', 'com.sap.vocabularies.UI.v1.DataField', false],
        'SecondaryInfo' => ['secondaryInfo', 'com.sap.vocabularies.UI.v1.DataField', false],
    ];

    public function __construct(
        public DataField|Path|null $headLine = null,
        public DataField|Path|null $title = null,
        public string|Path|null $imageUrl = null,
        public string|Path|null $typeImageUrl = null,
        public DataField|Path|null $mainInfo = null,
        public DataField|Path|null $secondaryInfo = null,
    ) {}
}
