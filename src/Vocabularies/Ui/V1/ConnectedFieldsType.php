<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Vocabularies\Core\V1\Dictionary;

/**
 * Group of semantically connected fields with a representation template and an optional label
 */
final readonly class ConnectedFieldsType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.ConnectedFieldsType';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'Template' => ['template', 'Edm.String', true],
        'Data' => ['data', 'Org.OData.Core.V1.Dictionary', true],
    ];

    /**
     * @param string|Path|null $label Label for the connected fields
     * @param string|Path|null $template Template for representing the connected fields
     * @param Dictionary|Path|null $data Dictionary of template variables
     */
    public function __construct(
        public string|Path|null $label = null,
        public string|Path|null $template = null,
        public Dictionary|Path|null $data = null,
    ) {}
}
