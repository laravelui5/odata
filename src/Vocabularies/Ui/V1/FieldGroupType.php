<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class FieldGroupType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.FieldGroupType';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'Data' => ['data', 'Collection(com.sap.vocabularies.UI.v1.DataFieldAbstract)', false],
    ];

    /**
     * @param list<DataFieldAbstract>|Path|null $data
     */
    public function __construct(
        public string|Path|null $label = null,
        public array|Path|null $data = null,
    ) {}
}
