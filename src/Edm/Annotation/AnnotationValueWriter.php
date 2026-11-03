<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Annotation;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LogicException;
use UnitEnum;

/**
 * Writes a PHP value as the annotation expression its declared vocabulary type asks for.
 *
 * The type is the qualified CSDL type of a term or record field, type definitions already resolved to their
 * underlying type: `Edm.Boolean` becomes `Bool="true"`, an enum type `EnumMember="Namespace.Type/Member"`, a complex
 * type the record of an {@see AbstractRecord}, `Collection(…)` a collection of its items. A {@see Path} stands in for
 * any value and is written as a value path (`Path="…"`).
 *
 * For the model-path types (`Edm.PropertyPath` and its kin) the two differ in meaning: a string is written as the path
 * itself (`PropertyPath="iso"`, §14.4.1.6), a Path as the value at that path (`Path="iso"`, §14.4.1.7). SAP's clients
 * read some of these terms as a value path — OpenUI5 reads `CodeList.StandardCode` through `…/$Path` — so the choice
 * stays with the annotator.
 *
 * @see OData CSDL XML v4.01 §14.3 (Constant Expressions), §14.4 (Dynamic Expressions)
 */
final class AnnotationValueWriter
{
    /** The CSDL constant expression of each primitive type. */
    private const array KINDS = [
        'Edm.Binary'                 => 'Binary',
        'Edm.Boolean'                => 'Bool',
        'Edm.Byte'                   => 'Int',
        'Edm.SByte'                  => 'Int',
        'Edm.Int16'                  => 'Int',
        'Edm.Int32'                  => 'Int',
        'Edm.Int64'                  => 'Int',
        'Edm.Date'                   => 'Date',
        'Edm.DateTimeOffset'         => 'DateTimeOffset',
        'Edm.Decimal'                => 'Decimal',
        'Edm.Double'                 => 'Float',
        'Edm.Single'                 => 'Float',
        'Edm.Duration'               => 'Duration',
        'Edm.Guid'                   => 'Guid',
        'Edm.String'                 => 'String',
        'Edm.TimeOfDay'              => 'TimeOfDay',
        'Edm.AnnotationPath'         => 'AnnotationPath',
        'Edm.ModelElementPath'       => 'ModelElementPath',
        'Edm.NavigationPropertyPath' => 'NavigationPropertyPath',
        'Edm.PropertyPath'           => 'PropertyPath',
        'Edm.AnyPropertyPath'        => 'PropertyPath',
    ];

    /**
     * @throws LogicException when the value does not fit the type
     */
    public static function write(mixed $value, string $type): AnnotationValueInterface
    {
        if ($value instanceof Path) {
            return $value->toAnnotationValue();
        }
        if ($value instanceof AbstractRecord) {
            return $value->toRecord();
        }
        if (str_starts_with($type, 'Collection(') && str_ends_with($type, ')')) {
            if (!is_array($value)) {
                throw new LogicException(sprintf('A %s takes a list, %s given.', $type, get_debug_type($value)));
            }
            $item = substr($type, 11, -1);

            return new CollectionAnnotationValue(...array_map(static fn($v) => self::write($v, $item), array_values($value)));
        }
        if ($value instanceof UnitEnum) {
            return new ConstantAnnotationValue('EnumMember', $type . '/' . $value->name);
        }
        if (is_array($value) && $value !== [] && array_filter($value, static fn($v) => !$v instanceof UnitEnum) === []) {
            // A flags enum: several members, space-separated (CSDL XML 4.01 §14.3.7).
            return new ConstantAnnotationValue(
                'EnumMember',
                implode(' ', array_map(static fn(UnitEnum $v) => $type . '/' . $v->name, array_values($value))),
            );
        }

        if (!str_starts_with($type, 'Edm.')) {
            // A vocabulary type that is not primitive: a complex type takes a record, an enum type a member.
            throw new LogicException(sprintf(
                'A %s takes a record or an enumeration member, %s given.',
                $type,
                get_debug_type($value),
            ));
        }

        $kind = self::KINDS[$type] ?? self::kindOf($value, $type);

        return new ConstantAnnotationValue($kind, self::lexical($value, $type));
    }

    /** The kind of a value whose type leaves it open (`Edm.Untyped`, `Edm.PrimitiveType`): taken from the value. */
    private static function kindOf(mixed $value, string $type): string
    {
        return match (true) {
            is_bool($value)   => 'Bool',
            is_int($value)    => 'Int',
            is_float($value)  => 'Float',
            is_string($value) => 'String',
            default           => throw new LogicException(
                sprintf('A value of type %s cannot be written for %s.', get_debug_type($value), $type),
            ),
        };
    }

    private static function lexical(mixed $value, string $type): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }
        throw new LogicException(sprintf('A value of type %s cannot be written for %s.', get_debug_type($value), $type));
    }
}
