<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

use Carbon\Carbon;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\PrimitiveTypeInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;

/**
 * Coerces row values to OData v4 wire formats for temporal primitive types.
 *
 * MySQL `DATETIME`/`DATE`/`TIME` columns accessed via raw query builder
 * yield strings like `2026-05-05 12:34:56` — no `T`, no offset. That is
 * not a valid `Edm.DateTimeOffset` literal (RFC 3339 §5.6 mandates `T`
 * and a `Z`/numeric offset) and breaks `Date.parse()` in Safari.
 *
 * Coercion happens once per row at JSON emission time:
 *   - Edm.DateTimeOffset → Carbon::parse($v)->toRfc3339String()
 *   - Edm.Date           → Carbon::parse($v)->toDateString()  (Y-m-d)
 *   - Edm.TimeOfDay      → Carbon::parse($v)->format('H:i:s')
 *
 * Already-correct RFC 3339 strings round-trip cleanly through Carbon::parse,
 * so the coercion is a no-op for callers that already format correctly.
 */
final readonly class RowCoercion
{
    /** @var array<string, callable(mixed): string> */
    private array $coercers;

    public function __construct(EntityTypeInterface $type)
    {
        $this->coercers = self::buildCoercers($type);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function apply(array $row): array
    {
        if ($this->coercers === []) {
            return $row;
        }

        foreach ($this->coercers as $name => $coercer) {
            if (array_key_exists($name, $row) && $row[$name] !== null) {
                $row[$name] = $coercer($row[$name]);
            }
        }

        return $row;
    }

    /** @return array<string, callable(mixed): string> */
    private static function buildCoercers(EntityTypeInterface $type): array
    {
        $coercers = [];

        foreach (self::collectProperties($type) as $property) {
            if ($property->isCollection()) {
                continue;
            }

            $propertyType = $property->getType();
            if (!$propertyType instanceof PrimitiveTypeInterface) {
                continue;
            }

            $coercer = self::coercerFor($propertyType->getPrimitiveType());
            if ($coercer !== null) {
                $coercers[$property->getName()] = $coercer;
            }
        }

        return $coercers;
    }

    /** @return iterable<PropertyInterface> */
    private static function collectProperties(EntityTypeInterface $type): iterable
    {
        $current = $type;
        while ($current !== null) {
            yield from $current->getDeclaredProperties();
            $current = $current->getBaseType();
        }
    }

    private static function coercerFor(EdmPrimitiveType $type): ?callable
    {
        return match ($type) {
            EdmPrimitiveType::DateTimeOffset => static fn (mixed $v): string => Carbon::parse($v)->toRfc3339String(),
            EdmPrimitiveType::Date           => static fn (mixed $v): string => Carbon::parse($v)->toDateString(),
            EdmPrimitiveType::TimeOfDay      => static fn (mixed $v): string => Carbon::parse($v)->format('H:i:s'),
            default                          => null,
        };
    }
}
