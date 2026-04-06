<?php

declare(strict_types=1);

arch('Edm has no external dependencies')
    ->expect('LaravelUi5\OData\Edm')
    ->not->toUse([
        'Illuminate',
        'LaravelUi5\OData\Service',
        'LaravelUi5\OData\Protocol',
        'LaravelUi5\OData\Driver',
    ]);

arch('Protocol never touches discovery or drivers')
    ->expect('LaravelUi5\OData\Protocol')
    ->not->toUse([
        'LaravelUi5\OData\Service\Discovery',
        'LaravelUi5\OData\Driver',
    ]);

arch('QueryPlan hierarchy is readonly')
    ->expect('LaravelUi5\OData\Protocol\Planning')
    ->toBeReadonly()
    ->ignoring([
        'LaravelUi5\OData\Protocol\Planning\OrderDirection',
        'LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator',
        'LaravelUi5\OData\Protocol\Planning\Expression\UnaryOperator',
        'LaravelUi5\OData\Protocol\Planning\Expression\FilterExpressionKind',
        'LaravelUi5\OData\Protocol\Planning\Expression\LambdaOperator',
        'LaravelUi5\OData\Protocol\Planning\Expression\FilterExpressionVisitor',
    ]);

arch('Driver has no HTTP knowledge')
    ->expect('LaravelUi5\OData\Driver')
    ->not->toUse('Illuminate\Http');
