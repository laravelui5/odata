<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

/**
 * Rebuilds an {@see ExpandList} with every expand whose target entity set is in the
 * dropped-set list removed — at any depth.
 *
 * Used by the read-authorization honest-partial model: a gated `$expand` is pruned from the
 * response (and reported via a `sap-messages` warning) rather than failing the whole read.
 * Read authorization is per entity SET, so a dropped set name removes every expand that points
 * at it, however deeply nested — no path bookkeeping, and no bypass via nesting.
 */
final readonly class ExpandPruner
{
    /**
     * @param list<string> $droppedSetNames entity-set names denied for read
     */
    public static function prune(ExpandList $list, array $droppedSetNames): ExpandList
    {
        if ($list->isEmpty() || $droppedSetNames === []) {
            return $list;
        }

        $kept = [];

        foreach ($list->items as $item) {
            if (in_array($item->targetSet->getName(), $droppedSetNames, true)) {
                continue; // gated set → drop this expand at this (and every) depth
            }

            $kept[] = $item->expand->isEmpty()
                ? $item
                : $item->withExpand(self::prune($item->expand, $droppedSetNames));
        }

        return new ExpandList($kept);
    }
}
