<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Discovery\Attributes;

use Attribute;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;

/**
 * Annotates one property of a discovered model, written on the model's class.
 *
 * ```php
 * #[ODataAnnotate('price', new ISOCurrency(new Path('currency')))]
 * #[ODataAnnotate('name', new Label('Product name'), new Description('Shown in lists'))]
 * class Product extends Model {}
 * ```
 *
 * The vocabulary attributes cannot sit on the class for this: there they annotate the entity type. `#[ODataAnnotate]`
 * names its target. The property must be one the entity type declares; discovery fails loud otherwise.
 *
 * A package that annotates columns across models binds a
 * {@see \LaravelUi5\OData\Service\Contracts\ColumnAnnotationResolverInterface}; on the same term and qualifier this
 * attribute wins.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class ODataAnnotate
{
    /** @var list<TypedAnnotationInterface> */
    public array $annotations;

    public function __construct(
        public string $property,
        TypedAnnotationInterface ...$annotations,
    ) {
        $this->annotations = array_values($annotations);
    }
}
