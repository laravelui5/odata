<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

/**
 * Marks an entity set the server never pages: it answers every row of a request in one response, whatever
 * `odata.pagination.default` and `odata.pagination.max` say and whether or not the client sends
 * `Prefer: odata.maxpagesize` — a preference the server may decline, and declines here (no `Preference-Applied`, no
 * `@odata.nextLink`).
 *
 * For small, bounded sets a client loads whole: a code list, which UI5 requests with `requestContexts(0, Infinity)`
 * and without `$top` — a page cut there renders every later code empty. A client that wants a part still asks for it
 * with `$top`, `$skip` or `$filter`. Not for a set that grows with the business: there the server's page is the guard.
 *
 * ```php
 * final readonly class Currencies extends AbstractEntitySet implements UnpagedEntitySetInterface { … }
 * ```
 */
// ROADMAP OP48; meta/specs/foundation/codelists-v1.0.md § 1.8 („delivered unpaged“).
interface UnpagedEntitySetInterface extends EntitySetResolverInterface
{
}
