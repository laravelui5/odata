<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class DraftRootType extends DraftNodeType
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.DraftRootType';

    public const array FIELDS = [
        'PreparationAction' => ['preparationAction', 'Edm.String', false],
        'ValidationFunction' => ['validationFunction', 'Edm.String', false],
        'ActivationAction' => ['activationAction', 'Edm.String', true],
        'DiscardAction' => ['discardAction', 'Edm.String', false],
        'EditAction' => ['editAction', 'Edm.String', false],
        'ResumeAction' => ['resumeAction', 'Edm.String', false],
        'NewAction' => ['newAction', 'Edm.String', false],
        'AdditionalNewActions' => ['additionalNewActions', 'Collection(Edm.String)', false],
        'ShareAction' => ['shareAction', 'Edm.String', false],
    ];

    /**
     * @param list<string>|Path|null $additionalNewActions
     */
    public function __construct(
        string|Path|null $preparationAction = null,
        string|Path|null $validationFunction = null,
        public string|Path|null $activationAction = null,
        public string|Path|null $discardAction = null,
        public string|Path|null $editAction = null,
        public string|Path|null $resumeAction = null,
        public string|Path|null $newAction = null,
        public array|Path|null $additionalNewActions = null,
        public string|Path|null $shareAction = null,
    ) {
        parent::__construct(
            preparationAction: $preparationAction,
            validationFunction: $validationFunction,
        );
    }
}
