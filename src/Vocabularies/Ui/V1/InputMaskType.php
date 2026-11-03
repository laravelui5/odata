<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class InputMaskType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.InputMaskType';

    public const array FIELDS = [
        'Mask' => ['mask', 'Edm.String', true],
        'PlaceholderSymbol' => ['placeholderSymbol', 'Edm.String', false],
        'Rules' => ['rules', 'Collection(com.sap.vocabularies.UI.v1.InputMaskRuleType)', false],
    ];

    /**
     * @param string|Path|null $mask The mask to be applied to the property or the parameter
     * @param string|Path|null $placeholderSymbol A single character symbol to be shown where the user can type a character
     * @param list<InputMaskRuleType>|Path|null $rules Rules that define valid values for one symbol in the mask
     */
    public function __construct(
        public string|Path|null $mask = null,
        public string|Path|null $placeholderSymbol = null,
        public array|Path|null $rules = null,
    ) {}
}
