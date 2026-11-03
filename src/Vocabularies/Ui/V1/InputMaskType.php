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
     * @param list<InputMaskRuleType>|Path|null $rules
     */
    public function __construct(
        public string|Path|null $mask = null,
        public string|Path|null $placeholderSymbol = null,
        public array|Path|null $rules = null,
    ) {}
}
