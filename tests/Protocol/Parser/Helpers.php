<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Tests\Protocol\Parser;

use LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeEnum;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Property\NavigationProperty;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Protocol\Parser\FilterParser;
use LaravelUi5\OData\Protocol\Parser\PropertyResolver;
use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpression;

/**
 * Build a test EntityType with typical properties for parser testing.
 *
 * Properties: id (Int32, key), origin (String), destination (String), priority (String).
 * Navigation: airports → airportType (id Int32, name String, code String).
 */
function parserEntityType(): EntityTypeInterface
{
    $idProp          = new Property('id', new PrimitiveType(PrimitiveTypeEnum::Int32));
    $originProp      = new Property('origin', new PrimitiveType(PrimitiveTypeEnum::String));
    $destinationProp = new Property('destination', new PrimitiveType(PrimitiveTypeEnum::String));
    $priorityProp    = new Property('priority', new PrimitiveType(PrimitiveTypeEnum::String));

    $airportType = new EntityType(
        namespace: 'Test',
        name: 'Airport',
        key: [new Property('id', new PrimitiveType(PrimitiveTypeEnum::Int32))],
        declaredProperties: [
            new Property('id', new PrimitiveType(PrimitiveTypeEnum::Int32)),
            new Property('name', new PrimitiveType(PrimitiveTypeEnum::String)),
            new Property('code', new PrimitiveType(PrimitiveTypeEnum::String)),
        ],
    );

    $airportsNav = new NavigationProperty(
        name: 'airports',
        targetType: $airportType,
        isCollection: true,
        referentialConstraints: ['origin' => 'code', 'destination' => 'code'],
    );

    $daNav = new NavigationProperty(
        name: 'da',
        targetType: $airportType,
        isCollection: true,
        referentialConstraints: ['destination' => 'code'],
    );

    return new EntityType(
        namespace: 'Test',
        name: 'Flight',
        key: [$idProp],
        declaredProperties: [$idProp, $originProp, $destinationProp, $priorityProp],
        declaredNavigationProperties: [$airportsNav, $daNav],
    );
}

/**
 * Parse a filter string and resolve to FilterExpression IR using the new parser.
 */
function translateFilter(string $expr, ?EntityTypeInterface $entityType = null): FilterExpression
{
    $entityType ??= parserEntityType();
    $parser   = new FilterParser();
    $resolver = new PropertyResolver();
    return $resolver->resolve($parser->parse($expr), $entityType);
}