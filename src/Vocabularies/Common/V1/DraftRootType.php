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
     * @param string|Path|null $preparationAction Action that prepares a draft document for later activation
     * @param string|Path|null $validationFunction Function that validates whether a draft document is ready for activation
     * @param string|Path|null $activationAction Action that activates a draft document
     * @param string|Path|null $discardAction Action that discards a draft document
     * @param string|Path|null $editAction Action that creates an edit draft
     * @param string|Path|null $resumeAction Action that resumes a draft document. The action re-acquires the exclusive lock if needed and checks if the related active document was not changed concurrently
     * @param string|Path|null $newAction Action that creates a new draft
     * @param list<string>|Path|null $additionalNewActions Additional actions beside the default POST or standard `NewAction`that create a new draft
     * @param string|Path|null $shareAction Action that shares a draft document with other users and restricts access to the listed users in their specified roles
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
