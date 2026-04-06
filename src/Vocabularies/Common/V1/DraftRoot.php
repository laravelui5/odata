<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

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

    public function __construct(
        public readonly string $activationAction,
        public readonly array $additionalNewActions,
        public readonly ?string $preparationAction = null,
        public readonly ?string $discardAction = null,
        public readonly ?string $editAction = null,
        public readonly ?string $resumeAction = null,
        public readonly ?string $newAction = null,
        public readonly ?string $shareAction = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Common.DraftRootType',
            new PropertyValue('PreparationAction', new ConstantAnnotationValue('String', (string) $this->preparationAction)),
            new PropertyValue('ActivationAction', new ConstantAnnotationValue('String', (string) $this->activationAction)),
            new PropertyValue('DiscardAction', new ConstantAnnotationValue('String', (string) $this->discardAction)),
            new PropertyValue('EditAction', new ConstantAnnotationValue('String', (string) $this->editAction)),
            new PropertyValue('ResumeAction', new ConstantAnnotationValue('String', (string) $this->resumeAction)),
            new PropertyValue('NewAction', new ConstantAnnotationValue('String', (string) $this->newAction)),
            new PropertyValue('AdditionalNewActions', new ConstantAnnotationValue('String', (string) $this->additionalNewActions)),
            new PropertyValue('ShareAction', new ConstantAnnotationValue('String', (string) $this->shareAction)),
        );
    }
}
