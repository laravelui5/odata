<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Discovery\Attributes;

use Attribute;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Type\TypeFacets;

/**
 * Declares a computed property on a discovered model: a property with no column, whose value is an SQL expression.
 *
 * ```php
 * #[ODataComputed('currency', EdmPrimitiveType::String, maxLength: 3)]
 * #[ODataAnnotate('price', new ISOCurrency(new Path('currency')))]
 * class ServiceItem extends Model {}
 * ```
 *
 * The declaration is static, so a cached service keeps it. The value comes from the bound
 * {@see \LaravelUi5\OData\Service\Contracts\ColumnExpressionResolverInterface}, asked with the model class and the
 * property name: the engine selects `(<expression>) AS <name>`, and `$filter`, `$orderby` and `$search` use the same
 * expression. A literal per request (a tenant's base currency) is the resolver's answer. A computed property without
 * an expression fails when it is queried.
 *
 * The engine annotates the property with `Core.Computed`. `#[ODataAnnotate]` reaches it like any column.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class ODataComputed
{
    public function __construct(
        public string           $name,
        public EdmPrimitiveType $type,
        public bool             $nullable = true,
        public ?int             $maxLength = null,
        public ?int             $precision = null,
        public ?int             $scale = null,
    ) {}

    public function facets(): TypeFacets
    {
        return new TypeFacets(
            nullable: $this->nullable,
            maxLength: $this->maxLength,
            precision: $this->precision,
            scale: $this->scale,
        );
    }
}
