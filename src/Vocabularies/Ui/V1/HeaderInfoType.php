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

    /**
     * @param string|Path|null $typeName Name of the main entity type
     * @param string|Path|null $typeNamePlural Plural form of the name of the main entity type
     * @param DataFieldAbstract|Path|null $title Title, e.g. for overview pages
     * @param DataFieldAbstract|Path|null $description Description, e.g. for overview pages
     * @param mixed $image Image for an instance of the entity type. If the property has a valid value, it can be used for the visualization of the instance. If it is not available or not valid the value of the property `ImageUrl` can be used instead.
     * @param string|Path|null $imageUrl Image URL for an instance of the entity type. If the property has a valid value, it can be used for the visualization of the instance. If it is not available or not valid the value of the property `TypeImageUrl` can be used instead.
     * @param string|Path|null $typeImageUrl Image URL for the entity type
     * @param string|Path|null $initials Latin letters to be used in case no `Image`, `ImageUrl`, or `TypeImageUrl` is present
     */
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
