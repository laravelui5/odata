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
     * @param string|Path|null $id May contain identifier to reference this instance from an external context
     * @param string|Path|null $text Name of the selection variant
     * @param list<ParameterAbstract>|Path|null $parameters Parameters of the selection variant
     * @param string|Path|null $filterExpression Filter string for query part of URL, without `$filter=`
     * @param list<SelectOptionType>|Path|null $selectOptions ABAP Select Options Pattern
     */
    public function __construct(
        public string|Path|null $id = null,
        public string|Path|null $text = null,
        public array|Path|null $parameters = null,
        public string|Path|null $filterExpression = null,
        public array|Path|null $selectOptions = null,
    ) {}
}
