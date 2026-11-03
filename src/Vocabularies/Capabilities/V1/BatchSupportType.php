<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class BatchSupportType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.BatchSupportType';

    public const array FIELDS = [
        'Supported' => ['supported', 'Edm.Boolean', false],
        'ContinueOnErrorSupported' => ['continueOnErrorSupported', 'Edm.Boolean', false],
        'ReferencesInRequestBodiesSupported' => ['referencesInRequestBodiesSupported', 'Edm.Boolean', false],
        'ReferencesAcrossChangeSetsSupported' => ['referencesAcrossChangeSetsSupported', 'Edm.Boolean', false],
        'EtagReferencesSupported' => ['etagReferencesSupported', 'Edm.Boolean', false],
        'RequestDependencyConditionsSupported' => ['requestDependencyConditionsSupported', 'Edm.Boolean', false],
        'SupportedFormats' => ['supportedFormats', 'Collection(Edm.String)', false],
    ];

    /**
     * @param list<string>|Path|null $supportedFormats
     */
    public function __construct(
        public bool|Path|null $supported = null,
        public bool|Path|null $continueOnErrorSupported = null,
        public bool|Path|null $referencesInRequestBodiesSupported = null,
        public bool|Path|null $referencesAcrossChangeSetsSupported = null,
        public bool|Path|null $etagReferencesSupported = null,
        public bool|Path|null $requestDependencyConditionsSupported = null,
        public array|Path|null $supportedFormats = null,
    ) {}
}
