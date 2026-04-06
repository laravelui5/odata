<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;

/**
 * Describes a chain of intermediate single-entity navigations that must be
 * resolved at execution time to determine the parent entity for the final
 * navigation segment.
 *
 * Example: /Projects(202)/customer/contact_customer
 *   rootSet  = Projects
 *   rootKey  = {id: 202}
 *   steps    = ['customer']          ← intermediate BelongsTo/HasOne nav props
 *   finalNav = 'contact_customer'    ← the final nav prop (on the resolved parent)
 */
final readonly class NavigationAnchor
{
    /**
     * @param EntitySetInterface $rootSet   The starting entity set (e.g. Projects).
     * @param KeyExpression      $rootKey   The key of the root entity (e.g. id=202).
     * @param list<string>       $steps     Intermediate navigation property names to follow.
     * @param string             $finalNav  The final navigation property name on the resolved parent.
     */
    public function __construct(
        public EntitySetInterface $rootSet,
        public KeyExpression      $rootKey,
        public array              $steps,
        public string             $finalNav,
    ) {}
}
