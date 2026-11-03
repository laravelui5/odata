<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Annotation;

use LaravelUi5\OData\Edm\Contracts\Annotation\RecordAnnotationValueInterface;
use LogicException;

/**
 * A value of a vocabulary complex type: one record in an annotation.
 *
 * Each complex type of a vocabulary is a subclass with one named, optional constructor parameter per field, its own
 * and the inherited ones (`new DataField(value: new Path('code'), label: 'Code')`). An abstract complex type is an
 * abstract class, so a term that takes `UI.DataFieldAbstract` accepts every concrete record deriving from it.
 *
 * `toRecord()` writes `<Record Type="…">` with only the fields that are set. A field the vocabulary requires
 * (`Nullable="false"`) is still optional in PHP: leaving it out fails here, when the schema is built, instead of
 * breaking every constructor call when a vocabulary adds a required field.
 *
 * @see OData CSDL XML v4.01 §14.4.10 (Record)
 */
abstract readonly class AbstractRecord
{
    /** The qualified name of the complex type (`com.sap.vocabularies.UI.v1.DataField`). */
    public const string TYPE = '';

    /**
     * Every field of the type, inherited ones first, by CSDL property name:
     * [PHP property, qualified CSDL type, nullable].
     *
     * @var array<string, array{0: string, 1: string, 2: bool}>
     */
    public const array FIELDS = [];

    /**
     * @throws LogicException when a field the vocabulary requires is not set
     */
    public function toRecord(): RecordAnnotationValueInterface
    {
        $values = [];
        foreach (static::FIELDS as $name => [$property, $type, $nullable]) {
            $value = $this->{$property};
            if ($value === null) {
                if (!$nullable) {
                    throw new LogicException(sprintf(
                        'The record %s requires the field %s (named argument $%s).',
                        static::TYPE,
                        $name,
                        $property,
                    ));
                }
                continue;
            }
            $values[] = new PropertyValue($name, AnnotationValueWriter::write($value, $type));
        }

        return new RecordAnnotationValue(static::TYPE, ...$values);
    }
}
