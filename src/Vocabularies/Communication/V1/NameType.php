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

    /**
     * @param string|Path|null $surname Surname or family name
     * @param string|Path|null $given Given name
     * @param string|Path|null $additional Additional names
     * @param string|Path|null $prefix Honorific prefix(es)
     * @param string|Path|null $suffix Honorific suffix(es)
     */
    public function __construct(
        public string|Path|null $surname = null,
        public string|Path|null $given = null,
        public string|Path|null $additional = null,
        public string|Path|null $prefix = null,
        public string|Path|null $suffix = null,
    ) {}
}
