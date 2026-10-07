<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

use Carbon\Carbon;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EnumTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\PrimitiveTypeInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;

/**
 * Coerces row values to OData v4 wire formats at JSON emission time.
 *
 * Two property kinds get coerced:
 *
 *   Temporal primitives — MySQL `DATETIME`/`DATE`/`TIME` columns accessed via
 *   raw query builder yield strings like `2026-05-05 12:34:56` (no `T`, no
 *   offset), which is not a valid `Edm.DateTimeOffset` literal (RFC 3339
 *   §5.6 mandates `T` and a `Z`/numeric offset) and breaks `Date.parse()`
 *   in Safari. Coercion uses Carbon:
 *     - Edm.DateTimeOffset → Carbon::parse($v)->toRfc3339String()
 *     - Edm.Date           → Carbon::parse($v)->toDateString()  (Y-m-d)
 *     - Edm.TimeOfDay      → Carbon::parse($v)->format('H:i:s')
 *   Already-correct strings round-trip cleanly.
 *
 *   EnumType properties — backing-int values are projected to the symbolic
 *   member name on the wire (`tier: 1` → `tier: "Single"`), the short form
 *   that `sap.ui.model.odata.type.Enum` parses by default. Unknown ints
 *   pass through unchanged so schema drift is visible rather than masked.

 *   Int64 and Decimal under `IEEE754Compatible=true` — written as JSON strings
 *   so a JavaScript client keeps every digit (a `decimal(19,6)` exceeds what a
 *   double holds). Without the parameter they stay numbers.
 *
 * Expanded navigation properties are coerced with their target type's rules,
 * at any depth.
 */
final readonly class RowCoercion
{
    /** @var array<string, callable(mixed): mixed> */
    private array $coercers;

    /** @var array<string, EntityTypeInterface> navigation property name → target type */
    private array $navigations;

    public function __construct(
        private EntityTypeInterface $type,
        private WireFormat $format = new WireFormat(),
    ) {
        $this->coercers    = self::buildCoercers($type, $format);
        $this->navigations = self::collectNavigations($type);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function apply(array $row): array
    {
        foreach ($this->coercers as $name => $coercer) {
            if (array_key_exists($name, $row) && $row[$name] !== null) {
                $row[$name] = $coercer($row[$name]);
            }
        }

        foreach ($this->navigations as $name => $target) {
            if (!isset($row[$name]) || !is_array($row[$name])) {
                continue;
            }
            $nested     = new self($target, $this->format);
            $row[$name] = array_is_list($row[$name])
                ? array_map(static fn (mixed $child): mixed => is_array($child) ? $nested->apply($child) : $child, $row[$name])
                : $nested->apply($row[$name]);
        }

        return $row;
    }

    /** @return array<string, callable(mixed): mixed> */
    private static function buildCoercers(EntityTypeInterface $type, WireFormat $format): array
    {
        $coercers = [];

        foreach (self::collectProperties($type) as $property) {
            if ($property->isCollection()) {
                continue;
            }

            $propertyType = $property->getType();

            if ($propertyType instanceof PrimitiveTypeInterface) {
                $coercer = self::coercerForPrimitive($propertyType->getPrimitiveType(), $format);
                if ($coercer !== null) {
                    $coercers[$property->getName()] = $coercer;
                }
                continue;
            }

            if ($propertyType instanceof EnumTypeInterface) {
                $coercers[$property->getName()] = self::coercerForEnum($propertyType);
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

    private static function coercerForPrimitive(EdmPrimitiveType $type, WireFormat $format): ?callable
    {
        return match (true) {
            $type === EdmPrimitiveType::DateTimeOffset => static fn (mixed $v): string => Carbon::parse($v)->toRfc3339String(),
            $type === EdmPrimitiveType::Date           => static fn (mixed $v): string => Carbon::parse($v)->toDateString(),
            $type === EdmPrimitiveType::TimeOfDay      => static fn (mixed $v): string => Carbon::parse($v)->format('H:i:s'),
            $format->ieee754Compatible && ($type === EdmPrimitiveType::Int64 || $type === EdmPrimitiveType::Decimal)
                                                       => self::numberAsString(...),
            default                                    => null,
        };
    }

    /**
     * A number as an exact decimal string. Drivers hand decimals over as strings
     * (MySQL, PostgreSQL) — those pass untouched; an int becomes its digits; a
     * float its shortest round-trip form, never exponent notation, which no
     * `Edm.Decimal` literal allows.
     */
    private static function numberAsString(mixed $v): mixed
    {
        if (is_string($v) || is_int($v)) {
            return (string) $v;
        }
        if (!is_float($v)) {
            return $v;
        }

        $repr = (string) json_encode($v);   // serialize_precision -1: the shortest exact form
        if (stripos($repr, 'e') === false) {
            return $repr;
        }

        // Expand "1.5e+21" / "1.0e-7" by moving the decimal point in the digit string,
        // so no binary rounding creeps back in.
        [$mantissa, $exponent] = explode('e', strtolower($repr));
        $sign     = str_starts_with($mantissa, '-') ? '-' : '';
        $mantissa = ltrim($mantissa, '-');
        [$int, $frac] = array_pad(explode('.', $mantissa), 2, '');
        $digits = $int . $frac;
        $point  = strlen($int) + (int) $exponent;

        if ($point <= 0) {
            $number = '0.' . str_repeat('0', -$point) . $digits;
        } elseif ($point >= strlen($digits)) {
            $number = $digits . str_repeat('0', $point - strlen($digits));
        } else {
            $number = substr($digits, 0, $point) . '.' . substr($digits, $point);
        }

        if (str_contains($number, '.')) {
            $number = rtrim(rtrim($number, '0'), '.');
        }
        $number = ltrim($number, '0');

        return $sign . ($number === '' || str_starts_with($number, '.') ? '0' . $number : $number);
    }

    /** @return array<string, EntityTypeInterface> */
    private static function collectNavigations(EntityTypeInterface $type): array
    {
        $navigations = [];
        $current     = $type;
        while ($current !== null) {
            foreach ($current->getDeclaredNavigationProperties() as $nav) {
                $navigations[$nav->getName()] ??= $nav->getTargetType();
            }
            $current = $current->getBaseType();
        }
        return $navigations;
    }

    /** @return callable(mixed): (string|int) */
    private static function coercerForEnum(EnumTypeInterface $type): callable
    {
        $valueToName = [];
        foreach ($type->getMembers() as $member) {
            $valueToName[$member->getValue()] = $member->getName();
        }

        return static function (mixed $v) use ($valueToName): string|int {
            $intValue = is_int($v) ? $v : (int) $v;
            return $valueToName[$intValue] ?? $intValue;
        };
    }
}
