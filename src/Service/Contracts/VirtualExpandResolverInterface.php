<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

use LaravelUi5\OData\Protocol\Planning\ExpandItem;

/**
 * Resolver that can serve as a virtual navigation expand on parent entities.
 *
 * Implement this alongside CustomEntitySetInterface when the entity set
 * should appear as a navigation property on discovered Eloquent models
 * without a real Eloquent relation backing it.
 *
 * Example: KPIs computed from multiple tables, expanded on User and Project.
 *
 *   class Kpis implements CustomEntitySetInterface, VirtualExpandResolverInterface
 *   {
 *       public function expandsOn(): array
 *       {
 *           return ['User' => 'kpis', 'Project' => 'kpis'];
 *       }
 *
 *       public function resolveExpand(array $parentRow, string $parentEntityType, ExpandItem $expand): array
 *       {
 *           // parentRow has the User/Project data; expand carries $filter, $select, etc.
 *           return [['kpi_id' => 1, 'name' => 'Hours', 'value' => 42.0]];
 *       }
 *   }
 *
 * Registration via discoverCustomEntitySet() automatically wires the
 * navigation properties and bindings on the parent entity types.
 */
interface VirtualExpandResolverInterface
{
    /**
     * Declare which entity types this resolver can be expanded on.
     *
     * Returns an associative array mapping entity type names to navigation
     * property names: ['User' => 'kpis', 'Project' => 'kpis'].
     *
     * @return array<string, string>
     */
    public function expandsOn(): array;

    /**
     * Resolve the expand for a single parent entity.
     *
     * @param array<string, mixed> $parentRow       The parent entity's data
     * @param string               $parentEntityType The parent entity type name (e.g. 'User')
     * @param ExpandItem           $expand           The expand item with filter, select, etc.
     * @return list<array<string, mixed>>            Child rows to attach under the nav property
     */
    public function resolveExpand(array $parentRow, string $parentEntityType, ExpandItem $expand): array;
}
