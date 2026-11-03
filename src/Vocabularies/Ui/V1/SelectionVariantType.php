<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class SelectionVariantType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.SelectionVariantType';

    public const array FIELDS = [
        'ID' => ['id', 'Edm.String', false],
        'Text' => ['text', 'Edm.String', false],
        'Parameters' => ['parameters', 'Collection(com.sap.vocabularies.UI.v1.ParameterAbstract)', false],
        'FilterExpression' => ['filterExpression', 'Edm.String', false],
        'SelectOptions' => ['selectOptions', 'Collection(com.sap.vocabularies.UI.v1.SelectOptionType)', false],
    ];

    /**
     * @param list<ParameterAbstract>|Path|null $parameters
     * @param list<SelectOptionType>|Path|null $selectOptions
     */
    public function __construct(
        public string|Path|null $id = null,
        public string|Path|null $text = null,
        public array|Path|null $parameters = null,
        public string|Path|null $filterExpression = null,
        public array|Path|null $selectOptions = null,
    ) {}
}
