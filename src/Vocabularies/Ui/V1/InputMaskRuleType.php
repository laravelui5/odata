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

    public function __construct(
        public string|Path|null $maskSymbol = null,
        public string|Path|null $regExp = null,
    ) {}
}
