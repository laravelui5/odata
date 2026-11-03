<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class NameType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Communication.v1.NameType';

    public const array FIELDS = [
        'surname' => ['surname', 'Edm.String', false],
        'given' => ['given', 'Edm.String', false],
        'additional' => ['additional', 'Edm.String', false],
        'prefix' => ['prefix', 'Edm.String', false],
        'suffix' => ['suffix', 'Edm.String', false],
    ];

    public function __construct(
        public string|Path|null $surname = null,
        public string|Path|null $given = null,
        public string|Path|null $additional = null,
        public string|Path|null $prefix = null,
        public string|Path|null $suffix = null,
    ) {}
}
