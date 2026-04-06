<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

use LaravelUi5\OData\Protocol\Planning\ComputedProperty;
use LaravelUi5\OData\Protocol\Planning\ExpandList;
use LaravelUi5\OData\Protocol\Planning\PropertySelectItem;
use LaravelUi5\OData\Protocol\Planning\SelectList;

/**
 * Shared helpers for $select handling in response handlers.
 */
final class SelectHelper
{
    /**
     * Returns an associative array of allowed column names (name => true),
     * or null when select-all (*) is in effect.
     *
     * Expanded navigation properties are always included regardless of $select.
     *
     * @return array<string, true>|null
     */
    /**
     * @param list<ComputedProperty> $compute
     */
    public static function allowedKeys(
        SelectList $select,
        ExpandList $expand = new ExpandList(),
        array      $compute = [],
    ): ?array {
        if ($select->isSelectAll()) {
            return null;
        }

        $keys = [];
        foreach ($select->items as $item) {
            if ($item instanceof PropertySelectItem) {
                $keys[$item->property->getName()] = true;
            }
        }

        // Expanded navigation properties are always present in the response.
        foreach ($expand->items as $item) {
            $keys[$item->property->getName()] = true;
        }

        // Computed properties are always present in the response.
        foreach ($compute as $computed) {
            $keys[$computed->alias] = true;
        }

        return $keys;
    }

    /**
     * Returns the OData context URL fragment for a $select clause.
     *
     * Empty string when select-all; otherwise "(prop1,prop2,...)".
     */
    public static function contextFragment(SelectList $select): string
    {
        if ($select->isSelectAll()) {
            return '';
        }

        $names = [];
        foreach ($select->items as $item) {
            if ($item instanceof PropertySelectItem) {
                $names[] = $item->property->getName();
            }
        }

        return $names !== [] ? '(' . implode(',', $names) . ')' : '';
    }
}
