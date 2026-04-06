<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\CollectionAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * The qualified names of explicitly bound operations that are supported on the target model element. These operations are in addition to any operations not annotated with RequiresExplicitBinding that are bound to the type of the target model element.
 * @see TypedAnnotationInterface
 */
final readonly class ExplicitOperationBindings implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Core.V1.ExplicitOperationBindings';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [];

        /** @param list<string> $value */
public function __construct(
        public readonly array $value = [],
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new CollectionAnnotationValue(
            ...array_map(
                static fn($v) => new ConstantAnnotationValue('String', (string) $v),
                $this->value,
            ),
        );
    }
}
