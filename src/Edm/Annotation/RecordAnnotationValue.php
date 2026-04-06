<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Annotation;

use LaravelUi5\OData\Edm\Contracts\Annotation\PropertyValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\RecordAnnotationValueInterface;

/**
 * A record annotation value composed of named property values.
 *
 * Accepts the optional record type name followed by zero or more
 * PropertyValueInterface instances as variadic arguments. The type name
 * corresponds to the Type attribute on a CSDL <Record> element.
 *
 * @see RecordAnnotationValueInterface
 * @see OData CSDL XML v4.01 §14.4.12 (Record)
 */
final readonly class RecordAnnotationValue implements RecordAnnotationValueInterface
{
    /** @var list<PropertyValueInterface> */
    private array $propertyValues;

    public function __construct(
        private ?string $type = null,
        PropertyValueInterface ...$propertyValues,
    ) {
        $this->propertyValues = array_values($propertyValues);
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    /** @return list<PropertyValueInterface> */
    public function getPropertyValues(): array
    {
        return $this->propertyValues;
    }

    public function getPropertyValue(string $name): ?PropertyValueInterface
    {
        foreach ($this->propertyValues as $pv) {
            if ($pv->getProperty() === $name) {
                return $pv;
            }
        }
        return null;
    }
}
