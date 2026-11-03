<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\SchemaInterface;
use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EnumMemberInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EnumTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeDefinitionInterface;

/**
 * Defines a recursive hierarchy.
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_ALL)]
final readonly class RecursiveHierarchy implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.RecursiveHierarchy';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
        ComplexTypeInterface::class,
        EnumTypeInterface::class,
        EnumMemberInterface::class,
        TypeDefinitionInterface::class,
        PropertyInterface::class,
        NavigationPropertyInterface::class,
        EntitySetInterface::class,
        SingletonInterface::class,
        FunctionImportInterface::class,
        EntityContainerInterface::class,
        FunctionParameterInterface::class,
        FunctionInterface::class,
        SchemaInterface::class,
    ];

    /**
     * @param string|Path|null $externalNodeKeyProperty Property holding the external human-readable key identifying the node
     * @param string|Path|null $nodeDescendantCountProperty Property holding the descendant count for a hierarchy node. The descendant count of a node is the number of its descendants in the hierarchy structure of the result considering only those nodes matching any specified $filter and $search. A property holding descendant counts has an integer data type.
     * @param string|Path|null $nodeDrillStateProperty Property holding the drill state of a hierarchy node. The drill state is indicated by one of the following string values: collapsed, expanded, or leaf. For an expanded node, its children are included in the result collection. For a collapsed node, the children are included in the entity set, but they are not part of the result collection. Retrieving them requires a relaxed filter expression or a separate request filtering on the parent node ID with the ID of the collapsed node. A leaf does not have any child in the entity set.
     */
    public function __construct(
        public string|Path|null $externalNodeKeyProperty = null,
        public string|Path|null $nodeDescendantCountProperty = null,
        public string|Path|null $nodeDrillStateProperty = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): AnnotationValueInterface
    {
        return (new RecursiveHierarchyType(
            externalNodeKeyProperty: $this->externalNodeKeyProperty,
            nodeDescendantCountProperty: $this->nodeDescendantCountProperty,
            nodeDrillStateProperty: $this->nodeDrillStateProperty,
        ))->toRecord();
    }
}
