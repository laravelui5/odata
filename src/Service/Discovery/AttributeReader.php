<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Discovery;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionParameter;
use ReflectionProperty;

/**
 * Reads PHP #[Attribute] annotations that implement TypedAnnotationInterface
 * from Reflection objects and returns them as AnnotationInterface instances.
 *
 * This is the bridge between PHP attribute syntax and the Edm annotation model.
 * It has no framework dependencies and produces no side effects — callers are
 * responsible for attaching the returned annotations to their EDM model objects.
 *
 * Usage:
 *   $annotations = (new AttributeReader)->readClass(new ReflectionClass($class));
 *   // pass $annotations into the EDM model object constructor via HasAnnotations
 *
 * Attributes that do not implement TypedAnnotationInterface are silently skipped;
 * they belong to other framework layers (routing, ORM, validation, etc.).
 *
 * @see TypedAnnotationInterface
 * @see AnnotationInterface
 * @see OData CSDL XML v4.01 §14.2 (Annotation)
 */
final readonly class AttributeReader
{
    /**
     * Reads class-level vocabulary annotations.
     * Covers: EntityType, ComplexType, EnumType, EntitySet, Singleton, EntityContainer.
     *
     * @return list<AnnotationInterface>
     */
    public function readClass(ReflectionClass $reflection): array
    {
        return $this->extract(
            $reflection->getAttributes(TypedAnnotationInterface::class, ReflectionAttribute::IS_INSTANCEOF),
        );
    }

    /**
     * Reads property-level vocabulary annotations.
     * Covers: Property, NavigationProperty.
     *
     * @return list<AnnotationInterface>
     */
    public function readProperty(ReflectionProperty $reflection): array
    {
        return $this->extract(
            $reflection->getAttributes(TypedAnnotationInterface::class, ReflectionAttribute::IS_INSTANCEOF),
        );
    }

    /**
     * Reads parameter-level vocabulary annotations.
     * Covers: FunctionParameter.
     *
     * @return list<AnnotationInterface>
     */
    public function readParameter(ReflectionParameter $reflection): array
    {
        return $this->extract(
            $reflection->getAttributes(TypedAnnotationInterface::class, ReflectionAttribute::IS_INSTANCEOF),
        );
    }

    /**
     * @param  ReflectionAttribute[] $attributes pre-filtered to TypedAnnotationInterface
     * @return list<AnnotationInterface>
     */
    private function extract(array $attributes): array
    {
        return array_values(array_map(
            static fn(ReflectionAttribute $a): AnnotationInterface => $a->newInstance()->toAnnotation(),
            $attributes,
        ));
    }
}
