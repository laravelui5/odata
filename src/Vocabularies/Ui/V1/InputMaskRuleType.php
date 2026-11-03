<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class InputMaskRuleType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.InputMaskRuleType';

    public const array FIELDS = [
        'MaskSymbol' => ['maskSymbol', 'Edm.String', true],
        'RegExp' => ['regExp', 'Edm.String', true],
    ];

    /**
     * @param string|Path|null $maskSymbol A symbol in the mask that stands for a regular expression which must be matched by the user input in every position where this symbol occurs
     * @param string|Path|null $regExp Regular expression that defines the valid values for the mask symbol
     */
    public function __construct(
        public string|Path|null $maskSymbol = null,
        public string|Path|null $regExp = null,
    ) {}
}
