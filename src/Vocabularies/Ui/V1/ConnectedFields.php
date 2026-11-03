<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Vocabularies\Core\V1\Dictionary;

/**
 * Group of semantically connected fields with a representation template and an optional label
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class ConnectedFields implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.ConnectedFields';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

    /**
     * @param string|Path|null $label Label for the connected fields
     * @param string|Path|null $template Template for representing the connected fields
     * @param Dictionary|Path|null $data Dictionary of template variables
     */
    public function __construct(
        public string|Path|null $label = null,
        public string|Path|null $template = null,
        public Dictionary|Path|null $data = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): AnnotationValueInterface
    {
        return (new ConnectedFieldsType(
            label: $this->label,
            template: $this->template,
            data: $this->data,
        ))->toRecord();
    }
}
