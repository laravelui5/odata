<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;

/**
 * Root entities of business documents that support the draft pattern
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class DraftRoot implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.DraftRoot';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
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
        public string|Path|null $preparationAction = null,
        public string|Path|null $validationFunction = null,
        public string|Path|null $activationAction = null,
        public string|Path|null $discardAction = null,
        public string|Path|null $editAction = null,
        public string|Path|null $resumeAction = null,
        public string|Path|null $newAction = null,
        public array|Path|null $additionalNewActions = null,
        public string|Path|null $shareAction = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new DraftRootType(
            preparationAction: $this->preparationAction,
            validationFunction: $this->validationFunction,
            activationAction: $this->activationAction,
            discardAction: $this->discardAction,
            editAction: $this->editAction,
            resumeAction: $this->resumeAction,
            newAction: $this->newAction,
            additionalNewActions: $this->additionalNewActions,
            shareAction: $this->shareAction,
        ))->toRecord();
    }
}
