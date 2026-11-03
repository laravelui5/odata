<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

readonly class DraftNodeType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.DraftNodeType';

    public const array FIELDS = [
        'PreparationAction' => ['preparationAction', 'Edm.String', false],
        'ValidationFunction' => ['validationFunction', 'Edm.String', false],
    ];

    /**
     * @param string|Path|null $preparationAction Action that prepares a draft document for later activation
     * @param string|Path|null $validationFunction Function that validates whether a draft document is ready for activation
     */
    public function __construct(
        public string|Path|null $preparationAction = null,
        public string|Path|null $validationFunction = null,
    ) {}
}
