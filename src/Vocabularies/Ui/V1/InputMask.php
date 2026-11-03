<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;

/**
 * Properties or parameters annotated with this term will get a mask in edit mode
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class InputMask implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.InputMask';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        PropertyInterface::class,
        FunctionParameterInterface::class,
    ];

    /**
     * @param string|Path|null $mask The mask to be applied to the property or the parameter
     * @param string|Path|null $placeholderSymbol A single character symbol to be shown where the user can type a character
     * @param list<InputMaskRuleType>|Path|null $rules Rules that define valid values for one symbol in the mask
     */
    public function __construct(
        public string|Path|null $mask = null,
        public string|Path|null $placeholderSymbol = null,
        public array|Path|null $rules = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new InputMaskType(
            mask: $this->mask,
            placeholderSymbol: $this->placeholderSymbol,
            rules: $this->rules,
        ))->toRecord();
    }
}
