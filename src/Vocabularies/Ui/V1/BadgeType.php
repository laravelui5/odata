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

    /**
     * @param DataField|Path|null $headLine Headline
     * @param DataField|Path|null $title Title
     * @param string|Path|null $imageUrl Image URL for an instance of the entity type. If the property has a valid value, it can be used for the visualization of the instance. If it is not available or not valid the value of the property `TypeImageUrl` can be used instead.
     * @param string|Path|null $typeImageUrl Image URL for the entity type
     * @param DataField|Path|null $mainInfo Main information on the business card
     * @param DataField|Path|null $secondaryInfo Additional information on the business card
     */
    public function __construct(
        public DataField|Path|null $headLine = null,
        public DataField|Path|null $title = null,
        public string|Path|null $imageUrl = null,
        public string|Path|null $typeImageUrl = null,
        public DataField|Path|null $mainInfo = null,
        public DataField|Path|null $secondaryInfo = null,
    ) {}
}
