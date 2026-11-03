<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\CodeList\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * An entity set containing the code list for currencies
 */
final readonly class CodeListSource extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.CodeList.v1.CodeListSource';

    public const array FIELDS = [
        'Url' => ['url', 'Edm.String', true],
        'CollectionPath' => ['collectionPath', 'Edm.String', true],
    ];

    /**
     * @param string|Path|null $url URL of a CSDL document describing an entity set for a code list
     * @param string|Path|null $collectionPath Name of the entity set for the code list
     */
    public function __construct(
        public string|Path|null $url = null,
        public string|Path|null $collectionPath = null,
    ) {}
}
