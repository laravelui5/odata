<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Serialization;

use LaravelUi5\OData\Edm\Contracts\AnnotatableInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\CollectionAnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\ConstantAnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\RecordAnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EnumTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\IncludedSchemaInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\ReferenceInterface;
use LaravelUi5\OData\Edm\Contracts\SchemaInterface;
use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeDefinitionInterface;
use SimpleXMLElement;

/**
 * Serialises a fully resolved EdmxInterface model to a CSDL XML string.
 *
 * This is the new-architecture counterpart to the legacy PathSegment\Metadata\XML.
 * It queries exclusively through Edm\Contracts\ interfaces and has no dependency
 * on the legacy Model singleton, Laravel, or any HTTP layer.
 *
 * Traversal follows Olingo's recursive-descent pattern: one private append*()
 * method per model element type, called top-down from serialize(). instanceof
 * dispatch is used only in appendAnnotationValue() to resolve the three value
 * subtypes (Constant, Record, Collection).
 *
 * @see OData CSDL XML v4.01 §4 (CSDL XML Document)
 * @see EdmxInterface
 */
final readonly class CsdlSerializer
{
    private const string EDM_NS   = 'http://docs.oasis-open.org/odata/ns/edm';
    private const string EDMX_NS  = 'http://docs.oasis-open.org/odata/ns/edmx';

    /**
     * Maps ConstantAnnotationValue kinds to their CSDL XML attribute names.
     * OData uses 'Int' for all integer kinds; our model uses 'Integer'.
     *
     * @see OData CSDL XML v4.01 §14.4.1–14.4.11
     */
    private const array KIND_TO_XML_ATTR = [
        'Integer' => 'Int',
    ];

    /**
     * Serialises the given EdmxInterface to a CSDL XML string.
     *
     * The returned string is UTF-8 encoded and starts with the XML
     * declaration, matching the format produced by the legacy XML.php.
     */
    public function serialize(EdmxInterface $edmx): string
    {
        $root = new SimpleXMLElement(
            '<?xml version="1.0" encoding="utf-8"?>'
            . '<edmx:Edmx xmlns:edmx="' . self::EDMX_NS . '"/>'
        );
        $root->addAttribute('Version', $edmx->getVersion());

        foreach ($edmx->getReferences() as $reference) {
            $this->appendReference($root, $reference);
        }

        $dataServices = $root->addChild('DataServices');

        foreach ($edmx->getSchemas() as $schema) {
            $this->appendSchema($dataServices, $schema, $edmx->getEntityContainer());
        }

        return (string) $root->asXML();
    }

    // ── References ────────────────────────────────────────────────────────────

    private function appendReference(SimpleXMLElement $root, ReferenceInterface $reference): void
    {
        $refEl = $root->addChild('Reference', null, self::EDMX_NS);
        $refEl->addAttribute('Uri', $reference->getUri());

        foreach ($reference->getIncludes() as $include) {
            $this->appendInclude($refEl, $include);
        }

        $this->appendAnnotations($refEl, $reference);
    }

    private function appendInclude(SimpleXMLElement $ref, IncludedSchemaInterface $include): void
    {
        $el = $ref->addChild('Include', null, self::EDMX_NS);
        $el->addAttribute('Namespace', $include->getNamespace());

        if ($include->getAlias() !== null) {
            $el->addAttribute('Alias', $include->getAlias());
        }
    }

    // ── Schema ────────────────────────────────────────────────────────────────

    /**
     * @see OData CSDL XML v4.01 §5 (Schema)
     */
    private function appendSchema(
        SimpleXMLElement $dataServices,
        SchemaInterface $schema,
        EntityContainerInterface $container,
    ): void {
        $schemaEl = $dataServices->addChild('Schema', null, self::EDM_NS);
        $schemaEl->addAttribute('Namespace', $schema->getNamespace());

        if ($schema->getAlias() !== null) {
            $schemaEl->addAttribute('Alias', $schema->getAlias());
        }

        // EntityContainer is placed first, per OData convention and legacy parity
        $this->appendEntityContainer($schemaEl, $container, $schema->getNamespace());

        foreach ($schema->getEnumTypes() as $enumType) {
            $this->appendEnumType($schemaEl, $enumType, $schema->getNamespace());
        }

        foreach ($schema->getTypeDefinitions() as $typeDef) {
            $this->appendTypeDefinition($schemaEl, $typeDef, $schema->getNamespace());
        }

        foreach ($schema->getEntityTypes() as $entityType) {
            $this->appendEntityType($schemaEl, $entityType, $schema->getNamespace());
        }

        foreach ($schema->getComplexTypes() as $complexType) {
            $this->appendComplexType($schemaEl, $complexType, $schema->getNamespace());
        }

        foreach ($schema->getFunctions() as $overloads) {
            foreach ($overloads as $function) {
                $this->appendFunction($schemaEl, $function, $schema->getNamespace());
            }
        }

        // Schema-level annotations block targeting the container
        $schemaAnnotations = $schema->getAnnotations();
        if ($schemaAnnotations !== []) {
            $annotationsEl = $schemaEl->addChild('Annotations');
            $annotationsEl->addAttribute('Target', $schema->getNamespace() . '.' . $container->getName());

            foreach ($schemaAnnotations as $annotation) {
                $this->appendAnnotation($annotationsEl, $annotation);
            }
        }
    }

    // ── EntityContainer ───────────────────────────────────────────────────────

    /**
     * @see OData CSDL XML v4.01 §13 (Entity Container)
     */
    private function appendEntityContainer(
        SimpleXMLElement $schema,
        EntityContainerInterface $container,
        string $namespace,
    ): void {
        $el = $schema->addChild('EntityContainer');
        $el->addAttribute('Name', $container->getName());

        if ($container->getExtendsName() !== null) {
            $el->addAttribute('Extends', $container->getExtendsName());
        }

        foreach ($container->getEntitySets() as $set) {
            $this->appendEntitySet($el, $set, $namespace);
        }

        foreach ($container->getSingletons() as $singleton) {
            $this->appendSingleton($el, $singleton, $namespace);
        }

        foreach ($container->getFunctionImports() as $import) {
            $this->appendFunctionImport($el, $import, $namespace);
        }

        $this->appendAnnotations($el, $container);
    }

    /**
     * @see OData CSDL XML v4.01 §13.2 (Entity Set)
     */
    private function appendEntitySet(
        SimpleXMLElement $container,
        EntitySetInterface $set,
        string $namespace,
    ): void {
        $el = $container->addChild('EntitySet');
        $el->addAttribute('Name', $set->getName());
        $el->addAttribute('EntityType', $set->getEntityType()->getQualifiedName());

        if (!$set->isIncludedInServiceDocument()) {
            $el->addAttribute('IncludeInServiceDocument', 'false');
        }

        foreach ($set->getNavigationPropertyBindings() as $binding) {
            $bindingEl = $el->addChild('NavigationPropertyBinding');
            $bindingEl->addAttribute('Path', $binding->getPath());
            $bindingEl->addAttribute('Target', $binding->getTarget());
        }

        $this->appendAnnotations($el, $set);
    }

    /**
     * @see OData CSDL XML v4.01 §13.3 (Singleton)
     */
    private function appendSingleton(
        SimpleXMLElement $container,
        SingletonInterface $singleton,
        string $namespace,
    ): void {
        $el = $container->addChild('Singleton');
        $el->addAttribute('Name', $singleton->getName());
        $el->addAttribute('Type', $singleton->getEntityType()->getQualifiedName());

        foreach ($singleton->getNavigationPropertyBindings() as $binding) {
            $bindingEl = $el->addChild('NavigationPropertyBinding');
            $bindingEl->addAttribute('Path', $binding->getPath());
            $bindingEl->addAttribute('Target', $binding->getTarget());
        }

        $this->appendAnnotations($el, $singleton);
    }

    /**
     * @see OData CSDL XML v4.01 §13.6 (Function Import)
     */
    private function appendFunctionImport(
        SimpleXMLElement $container,
        FunctionImportInterface $import,
        string $namespace,
    ): void {
        $el = $container->addChild('FunctionImport');
        $el->addAttribute('Name', $import->getName());
        $el->addAttribute('Function', $namespace . '.' . $import->getFunction()->getName());

        if ($import->getEntitySet() !== null) {
            $el->addAttribute('EntitySet', $import->getEntitySet());
        }

        if ($import->isIncludedInServiceDocument()) {
            $el->addAttribute('IncludeInServiceDocument', 'true');
        }

        $this->appendAnnotations($el, $import);
    }

    // ── Types ─────────────────────────────────────────────────────────────────

    /**
     * @see OData CSDL XML v4.01 §10 (Enumeration Type)
     */
    private function appendEnumType(
        SimpleXMLElement $schema,
        EnumTypeInterface $enumType,
        string $namespace,
    ): void {
        $el = $schema->addChild('EnumType');
        $el->addAttribute('Name', $enumType->getName());
        $el->addAttribute('UnderlyingType', $enumType->getUnderlyingType()->value);
        $el->addAttribute('IsFlags', $enumType->isFlags() ? 'true' : 'false');

        foreach ($enumType->getMembers() as $member) {
            $memberEl = $el->addChild('Member');
            $memberEl->addAttribute('Name', $member->getName());
            $memberEl->addAttribute('Value', (string) $member->getValue());
            $this->appendAnnotations($memberEl, $member);
        }

        $this->appendAnnotations($el, $enumType);
    }

    /**
     * @see OData CSDL XML v4.01 §11 (Type Definition)
     */
    private function appendTypeDefinition(
        SimpleXMLElement $schema,
        TypeDefinitionInterface $typeDef,
        string $namespace,
    ): void {
        $el = $schema->addChild('TypeDefinition');
        $el->addAttribute('Name', $typeDef->getName());
        $el->addAttribute('UnderlyingType', $typeDef->getUnderlyingType()->value);

        $facets = $typeDef->getFacets();
        if ($facets !== null) {
            $this->appendFacetAttributes($el, $facets);
        }

        $this->appendAnnotations($el, $typeDef);
    }

    /**
     * @see OData CSDL XML v4.01 §6 (Entity Type)
     */
    private function appendEntityType(
        SimpleXMLElement $schema,
        EntityTypeInterface $type,
        string $namespace,
    ): void {
        $el = $schema->addChild('EntityType');
        $el->addAttribute('Name', $type->getName());

        if ($type->getBaseType() !== null) {
            $el->addAttribute('BaseType', $type->getBaseType()->getQualifiedName());
        }

        if ($type->isAbstract()) {
            $el->addAttribute('Abstract', 'true');
        }

        if ($type->isOpen()) {
            $el->addAttribute('OpenType', 'true');
        }

        if ($type->hasStream()) {
            $el->addAttribute('HasStream', 'true');
        }

        $key = $type->getKey();
        if ($key !== []) {
            $keyEl = $el->addChild('Key');
            foreach ($key as $keyProp) {
                $keyEl->addChild('PropertyRef')->addAttribute('Name', $keyProp->getName());
            }
        }

        foreach ($type->getDeclaredProperties() as $property) {
            $this->appendProperty($el, $property);
        }

        foreach ($type->getDeclaredNavigationProperties() as $navProp) {
            $this->appendNavigationProperty($el, $navProp);
        }

        $this->appendAnnotations($el, $type);
    }

    /**
     * @see OData CSDL XML v4.01 §9 (Complex Type)
     */
    private function appendComplexType(
        SimpleXMLElement $schema,
        ComplexTypeInterface $type,
        string $namespace,
    ): void {
        $el = $schema->addChild('ComplexType');
        $el->addAttribute('Name', $type->getName());

        if ($type->isAbstract()) {
            $el->addAttribute('Abstract', 'true');
        }

        if ($type->isOpen()) {
            $el->addAttribute('OpenType', 'true');
        }

        foreach ($type->getDeclaredProperties() as $property) {
            $this->appendProperty($el, $property);
        }

        $this->appendAnnotations($el, $type);
    }

    // ── Properties ────────────────────────────────────────────────────────────

    /**
     * @see OData CSDL XML v4.01 §7 (Structural Property)
     */
    private function appendProperty(SimpleXMLElement $parent, PropertyInterface $property): void
    {
        $el = $parent->addChild('Property');
        $el->addAttribute('Name', $property->getName());

        $typeName = $property->getType()->getQualifiedName();
        if ($property->isCollection()) {
            $typeName = 'Collection(' . $typeName . ')';
        }
        $el->addAttribute('Type', $typeName);

        $facets = $property->getFacets();
        if ($facets !== null) {
            $this->appendFacetAttributes($el, $facets);
        } else {
            // Nullable defaults to true per spec; emit only when false
            if (!$property->isCollection()) {
                // No facets means nullable is not explicitly constrained;
                // Omit the attribute to let the spec default (true) apply.
            }
        }

        if ($property->getDefaultValue() !== null) {
            $el->addAttribute('DefaultValue', $property->getDefaultValue());
        }

        $this->appendAnnotations($el, $property);
    }

    /**
     * @see OData CSDL XML v4.01 §8 (Navigation Property)
     */
    private function appendNavigationProperty(
        SimpleXMLElement $parent,
        NavigationPropertyInterface $navProp,
    ): void {
        $el = $parent->addChild('NavigationProperty');
        $el->addAttribute('Name', $navProp->getName());

        $typeName = $navProp->getTargetType()->getQualifiedName();
        if ($navProp->isCollection()) {
            $typeName = 'Collection(' . $typeName . ')';
        }
        $el->addAttribute('Type', $typeName);

        if (!$navProp->isCollection()) {
            $el->addAttribute('Nullable', $navProp->isNullable() ? 'true' : 'false');
        }

        if ($navProp->getPartnerName() !== null) {
            $el->addAttribute('Partner', $navProp->getPartnerName());
        }

        if ($navProp->isContainmentTarget()) {
            $el->addAttribute('ContainsTarget', 'true');
        }

        foreach ($navProp->getReferentialConstraints() as $dependent => $principal) {
            $constraintEl = $el->addChild('ReferentialConstraint');
            $constraintEl->addAttribute('Property', $dependent);
            $constraintEl->addAttribute('ReferencedProperty', $principal);
        }

        if ($navProp->getOnDeleteAction() !== null) {
            $el->addChild('OnDelete')->addAttribute('Action', $navProp->getOnDeleteAction());
        }

        $this->appendAnnotations($el, $navProp);
    }

    // ── Functions ─────────────────────────────────────────────────────────────

    /**
     * @see OData CSDL XML v4.01 §12.3 (Function)
     */
    private function appendFunction(
        SimpleXMLElement $schema,
        FunctionInterface $function,
        string $namespace,
    ): void {
        $el = $schema->addChild('Function');
        $el->addAttribute('Name', $function->getName());
        $el->addAttribute('IsBound', $function->isBound() ? 'true' : 'false');

        if ($function->isComposable()) {
            $el->addAttribute('IsComposable', 'true');
        }

        if ($function->getEntitySetPath() !== null) {
            $el->addAttribute('EntitySetPath', (string) $function->getEntitySetPath());
        }

        foreach ($function->getParameters() as $parameter) {
            $this->appendFunctionParameter($el, $parameter);
        }

        if ($function->getReturnType() !== null) {
            $returnEl = $el->addChild('ReturnType');
            $typeName = $function->getReturnType()->getQualifiedName();
            if ($function->returnsCollection()) {
                $typeName = 'Collection(' . $typeName . ')';
            }
            $returnEl->addAttribute('Type', $typeName);
            $returnEl->addAttribute('Nullable', $function->isReturnTypeNullable() ? 'true' : 'false');
        }

        $this->appendAnnotations($el, $function);
    }

    /**
     * @see OData CSDL XML v4.01 §12.9 (Parameter)
     */
    private function appendFunctionParameter(
        SimpleXMLElement $function,
        FunctionParameterInterface $parameter,
    ): void {
        $el = $function->addChild('Parameter');
        $el->addAttribute('Name', $parameter->getName());

        $typeName = $parameter->getType()->getQualifiedName();
        if ($parameter->isCollection()) {
            $typeName = 'Collection(' . $typeName . ')';
        }
        $el->addAttribute('Type', $typeName);
        $el->addAttribute('Nullable', $parameter->isNullable() ? 'true' : 'false');

        $facets = $parameter->getFacets();
        if ($facets !== null) {
            $this->appendFacetAttributes($el, $facets);
        }

        $this->appendAnnotations($el, $parameter);
    }

    // ── Facets ────────────────────────────────────────────────────────────────

    /**
     * Emits the applicable facet attributes onto the given element.
     * Nullable is always written; optional facets are written only when present.
     *
     * @see OData CSDL XML v4.01 §7.2 (Type Facets)
     */
    private function appendFacetAttributes(
        SimpleXMLElement $el,
        \LaravelUi5\OData\Edm\Contracts\Type\TypeFacetsInterface $facets,
    ): void {
        $el->addAttribute('Nullable', $facets->isNullable() ? 'true' : 'false');

        if ($facets->getMaxLength() !== null) {
            $el->addAttribute(
                'MaxLength',
                $facets->getMaxLength() === PHP_INT_MAX ? 'max' : (string) $facets->getMaxLength()
            );
        }

        if ($facets->getPrecision() !== null) {
            $el->addAttribute('Precision', (string) $facets->getPrecision());
        }

        if ($facets->getScale() !== null) {
            $el->addAttribute(
                'Scale',
                $facets->getScale() === -1 ? 'variable' : (string) $facets->getScale()
            );
        }

        if ($facets->isUnicode() !== null && !$facets->isUnicode()) {
            // Only emit Unicode="false" — the default is true, so omit when true
            $el->addAttribute('Unicode', 'false');
        }

        if ($facets->getSrid() !== null) {
            $el->addAttribute('SRID', (string) $facets->getSrid());
        }
    }

    // ── Annotations ───────────────────────────────────────────────────────────

    /**
     * Appends all annotations on the given annotatable element as
     * <Annotation> child elements on $parent.
     *
     * @see OData CSDL XML v4.01 §14.2 (Annotation)
     */
    private function appendAnnotations(SimpleXMLElement $parent, AnnotatableInterface $element): void
    {
        foreach ($element->getAnnotations() as $annotation) {
            $this->appendAnnotation($parent, $annotation);
        }
    }

    private function appendAnnotation(SimpleXMLElement $parent, AnnotationInterface $annotation): void
    {
        $el = $parent->addChild('Annotation');
        $el->addAttribute('Term', $annotation->getTerm());

        if ($annotation->getQualifier() !== null) {
            $el->addAttribute('Qualifier', $annotation->getQualifier());
        }

        if ($annotation->getValue() !== null) {
            $this->appendAnnotationValue($el, $annotation->getValue());
        }
    }

    /**
     * Dispatches annotation value serialization by subtype.
     *
     * This is the only point in the serializer where instanceof is used.
     * The three subtypes map to distinct XML structures:
     *   - Constant  → inline XML attribute (e.g. Bool="true", String="x")
     *   - Record    → <Record> child element with <PropertyValue> children
     *   - Collection → <Collection> child element with recursive items
     *
     * @see OData CSDL XML v4.01 §14.4 (Expression)
     */
    private function appendAnnotationValue(
        SimpleXMLElement $annotEl,
        AnnotationValueInterface $value,
    ): void {
        if ($value instanceof ConstantAnnotationValueInterface) {
            $xmlAttr = self::KIND_TO_XML_ATTR[$value->getKind()] ?? $value->getKind();
            $annotEl->addAttribute($xmlAttr, $value->getValue());
            return;
        }

        if ($value instanceof RecordAnnotationValueInterface) {
            $recordEl = $annotEl->addChild('Record');
            if ($value->getType() !== null) {
                $recordEl->addAttribute('Type', $value->getType());
            }
            foreach ($value->getPropertyValues() as $pv) {
                $pvEl = $recordEl->addChild('PropertyValue');
                $pvEl->addAttribute('Property', $pv->getProperty());
                $this->appendAnnotationValue($pvEl, $pv->getValue());
            }
            return;
        }

        if ($value instanceof CollectionAnnotationValueInterface) {
            $collEl = $annotEl->addChild('Collection');
            foreach ($value->getItems() as $item) {
                if ($item instanceof ConstantAnnotationValueInterface) {
                    // Constant items in a collection become typed child elements,
                    // e.g. <String>value</String> rather than attributes.
                    $xmlTag = self::KIND_TO_XML_ATTR[$item->getKind()] ?? $item->getKind();
                    $collEl->addChild($xmlTag, htmlspecialchars($item->getValue(), ENT_XML1));
                } else {
                    // Record or nested Collection items recurse normally.
                    // A child element is added and the value serialised into it.
                    // For Record items the tag is <Record>; we create it via
                    // the recursive call using a temporary wrapper approach.
                    $this->appendAnnotationValueAsChild($collEl, $item);
                }
            }
        }
    }

    /**
     * Appends a non-constant annotation value as a child element of $parent.
     *
     * Used when a Record or Collection value appears inside a Collection —
     * the parent is the <Collection> element, not an <Annotation> element,
     * so the term/qualifier wrapping is absent.
     */
    private function appendAnnotationValueAsChild(
        SimpleXMLElement $parent,
        AnnotationValueInterface $value,
    ): void {
        if ($value instanceof RecordAnnotationValueInterface) {
            $recordEl = $parent->addChild('Record');
            if ($value->getType() !== null) {
                $recordEl->addAttribute('Type', $value->getType());
            }
            foreach ($value->getPropertyValues() as $pv) {
                $pvEl = $recordEl->addChild('PropertyValue');
                $pvEl->addAttribute('Property', $pv->getProperty());
                $this->appendAnnotationValue($pvEl, $pv->getValue());
            }
            return;
        }

        if ($value instanceof CollectionAnnotationValueInterface) {
            $collEl = $parent->addChild('Collection');
            foreach ($value->getItems() as $item) {
                $this->appendAnnotationValueAsChild($collEl, $item);
            }
        }
    }
}
