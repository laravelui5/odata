<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression;

final readonly class FunctionInvocationPlan extends QueryPlan
{
    /**
     * @param array<string, LiteralExpression> $parameters  Parameter name → literal, validated against FunctionParameterInterface[].
     */
    public function __construct(
        public FunctionImportInterface $import,
        public array                   $parameters,
    ) {}
}
