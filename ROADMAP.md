# LaravelUi5 OData — Roadmap

Tracks scheduled work for `laravelui5/odata`. Shipped releases live in
[`CHANGELOG.md`](./CHANGELOG.md), each tagged with the version that carried it.

- **Pending** holds items queued for an upcoming slice — design questions settled, scope known. Earlier-stage ideas live as atoms in a private notes repository and are lifted into Pending once they're ready to schedule.

Releases follow the consumer-bump dance: tag the package → Satis rebuilds → patch the in-house consumers (`laravelui5/core`, `laravelui5/sdk`, `pragmatiqu/timesheet.biz`) → smoke-test before the next slice begins. Companion to `laravelui5/core`'s ROADMAP; the two move together when contract surface is shared.

---

## Known gaps

The documentation describes the **target** state of each surface, so a page may promise behaviour a
release has not caught up with yet. Those places are marked **planned** and point here; each one is
an entry below.

| Gap | Today | Queued |
|:---|:---|:---|
| **Service document** | Neither `includedInServiceDocument` switch is read, and function imports are never listed | Both honoured; `FunctionImport`'s default moves to `true` |
| **Cached `$metadata`** | `$metadata` is serialized on every request; `cachedMetadataXMLPath()` is read by nothing | `odata:cache` writes the document and the serializer streams it; the cold path is unchanged |

## Pending

## [ ] `OP01` EDM cache is keyed by class location, not service identity — load-side silent collision

Surfaced 2026-06-11 (`pragmatiqu/timesheet.biz`) building a second, **route-composed** service
(`ExcelService`, via the 1.0.6 `OData::forService()` seam) beside the registry-cached
`TimesheetService`. `EdmxLoader::cacheDir()` (`dirname(classFile)/Edm`) and `edmxClassName()`
(`{ns}\Edm\Edmx`) key the warm cache by the service class's **directory + namespace**, not by
service identity. Two services sharing a namespace → the second's `schema()` warm path loads the
FIRST's cached EDMX and serves it, **silently**. `ExcelService` returned `TimesheetService`'s full
60-set `$metadata` on `/excel` until it was moved to its own `App\Excel` namespace.

Workaround (in place downstream): one service per namespace.

**Write side shipped (1.0.7):** `odata:cache` now fails loud when two services resolve to the same
cache dir, before writing anything (see Done). **Still pending — the load side:**
`EdmxLoader::forService` should fail loud (or key the cache by `serviceUri`/`route()` / a
service-provided cache key) so a pre-existing or hand-placed collision can't be silently served.
Silent-wrong-schema is the worst failure mode — same family as the 1.0.5 collision.

Reasoned through in the internal route-composition notes.
<!-- Atom [[ODATA_ALTERNATIVE_CLIENT_DEDICATED_SERVICE]] · spec docs/meta/specs/odata-route-composition.md OP5. -->

## [ ] `OP03` Virtual `$expand` is resolved only on Eloquent-backed sets — custom (SQL) entity sets ignore it

Surfaced 2026-07-08 (`laravelui5/sdk` — `sdk-host` Partners, building the ui5-partners **object-page
header** pattern, PA-DETAILS-A-10). A header binds ONE keyed resource and lets its variable-length
adornments ride along as expanded nav collections — the pattern `pragmatiqu/timesheet.biz`'s `App\OData\Kpis`
proves (`Users(11)?$expand=kpis`). We tried the same on `PartnerDetails` — a **computed `fromSub` custom
set** (identity + count-tab totals) — with a `PartnerStructuralRolesExpand` (`CustomEntitySetInterface` +
`VirtualExpandResolverInterface`) attaching a `structural_roles` collection.

**Metadata wires correctly** — `$metadata` shows the `structural_roles` `NavigationProperty` on
`PartnerDetail` + its `NavigationPropertyBinding` (both `applyVirtualExpandsToDiscovery` and
`applyVirtualExpandsToBuilder` cover it). **Runtime does not:** `resolveExpand()` is invoked **only** from
`EloquentEntitySetResolver::resolveVirtualExpand()` (`:399`). The `SqlEntitySetResolver` that serves custom
`AbstractEntitySet`s (`resolve()` / `resolveOne()`) never reads `$plan->expand`, so the collection is never
materialized — the response omits it entirely (not even `[]`). `Kpis` works only because it expands on
`User`/`Project`, which are `discoverModel` entities served by the Eloquent resolver. **A computed header
resource cannot carry a virtual expand today.**

**Fix:** teach `SqlEntitySetResolver` to apply virtual expands after producing each row — mirror
`EloquentEntitySetResolver`'s `resolveVirtualExpand` + `attachExpandedRelations` (needs a schema handle to
look up `$item->targetSet`'s resolver; check `instanceof VirtualExpandResolverInterface`; attach under the
nav name). Closes the gap for every SQL-backed custom set that wants header-adornment collections, not just
this one — and unblocks the canonical **object-page-header skill**.

**Resolved downstream by not being custom (2026-07-08):** `ui5-partners` reverted `PartnerDetails` from a
computed `fromSub` set to an **Eloquent** `discoverModel(PartnerDetail)` header and moved its counts to
virtual `$expand`s (`authorization` summary, `delegations` collection, `settings` summary) — the
object-page-header pattern ([choosing a resolver](https://laravelui5.com/odata/resolvers/choosing-a-resolver)).
<!-- Atom: docs/meta/atoms/OBJECT_PAGE_HEADER_AS_ELOQUENT_EXPAND.md. -->
So this item is **no
longer blocking** — it stands for the residual case where a header genuinely *must* be a computed set and
still wants expand-able adornments. Until then, prefer an Eloquent header over a custom one.

Same resolver-family gap as the 2026-07-06 Edm-coercion item above (both: `SqlEntitySetResolver` lacks a
capability `EloquentEntitySetResolver` has).

## [ ] `OP04` A lean / no-hydration path for expand-less `discoverModel` reads — reclaim streaming speed on lists

Surfaced 2026-07-08 (`laravelui5/sdk` — measuring the Partners master/detail migration). The engine's whole
speed advantage over `flat3/lodata` is that it **streams optimized SQL rows and does not hydrate models**.
`SqlEntitySetResolver` (custom sets) does exactly that — `->cursor()` → `stdClass` → `(array)`.
`EloquentEntitySetResolver` (`discoverModel` sets), by contrast, **always** `get()/cursor()` → `Model` →
`toArray()`. Measured on 180 rows (dev SQLite): raw **0.82 ms**, Eloquent cast-free **27.97 ms** (~34×),
Eloquent with enum/date casts **60.77 ms** (~74×). Casts roughly double the cost; enum casts dominate.

Consequence: a `discoverModel` set is the right tool for a **small-N** read (a keyed detail + a few
`$expand`s — hydration is ~1 ms, negligible) but a **speed regression for a large-N list** (a Master, an
export, an aggregate). Today that forces large lists to stay custom raw-SQL sets — correct, but it blocks
the clean "one Eloquent model, one set for master + detail" shape (a list read of the Eloquent set would
hydrate every row). See [choosing a resolver](https://laravelui5.com/odata/resolvers/choosing-a-resolver),
which carries the per-row measurements.
<!-- Atom: docs/meta/atoms/ODATA_STREAMING_NOT_HYDRATION.md. -->

**Fix:** when an `EloquentEntitySetResolver` read has **no `$expand`** and no properties needing cast/accessor
transformation (or those can be applied cheaply on the raw row), take a **lean path** — `->toBase()->get()`
(or `->cursor()`) yielding `stdClass`/arrays directly, skipping model instantiation + `toArray()`. Same rows
on the wire, streaming speed. Gate it on the query plan (`$expand` empty) so expand reads keep hydrating (they
need the relations). This would let one discovered set serve a **fast lean list** (`$select`, no expand) AND a
**rich detail** (keyed, `$expand`) — unifying master/detail on Eloquent without the hydration tax.

In the spirit of the engine: the fast path should be the default, hydration the opt-in that expands require.

## [ ] `OP05` `@odata.nextLink` drops every query option except `$skip` — page 2 is unfiltered

Surfaced 2026-08-22 (docs/code drift audit of `docs/odata/`). `EntitySetHandler.php:93` builds the
server-driven-paging continuation as

```php
$nextLink = $serviceRoot . $setName . '?$skip=' . $skip;
```

The request's other system query options are not carried over. A client that pages through
`Products?$filter=active eq true&$orderby=price desc&$select=name,price` gets a correct first page,
then follows `@odata.nextLink` to `Products?$skip=200` — **unfiltered, unsorted, unprojected, and
without `$count`**. Page 2 is a different result set than page 1 claimed to be paging through, and
nothing in the response says so.

Same silent-wrong-value family as the SQL-coercion and cache items: the response is well-formed and
the client has no way to detect the substitution. It is arguably worse, because the client did
exactly what the protocol told it to do — a next link stands for the remainder of *the same*
collection query, so a conforming client (the UI5 v4 model's paged `ODataListBinding`, Excel's
"load more") follows it without re-sending its own options.

**Fix:** reconstruct the next link from the request's full query string with `$skip` replaced (and
`$top`, where the client sent one, decremented by the rows already emitted) rather than composing it
from the set name alone. `EntitySetHandler` already receives `$plan`, but the plan is the parsed
form; the honest source is the original query string, so the service root/URL builder should carry
it in. Worth covering with a protocol test that pages a filtered collection to exhaustion and
asserts every page satisfies the filter.

Until it is fixed, `docs/odata/query-options/pagination.md` carries a warning and tells clients to
re-send their options on the follow-up request.

**Same cause, second symptom (confirmed 2026-09-18, `acme`, Core 2.11.0 / odata 3.0.6).** The link
is built from the *target set's* name, not the request's path, so a paged **navigation collection**
(`Products(1)/Orders`) links to `Orders?$skip=…`, and page 2 is the whole target set. Live check on
a Core app: `Users?$filter=id gt 50&$select=name&$orderby=name` with `Prefer: odata.maxpagesize=5`
returned five matching rows, then a next link `Users?$skip=5` whose page held ids 6–10, all columns,
ordered by id.

**Blast radius (assessed 2026-09-18).** One construction site (`EntitySetHandler.php:93`). The
request path and query have to be passed down explicitly, controller or `BatchHandler` →
`ReadGate::execute()` → `Engine` → `EntitySetHandler`: inside `$batch` the gate holds the **outer**
request, so the handler cannot read the inner query from it. Three source files, no use of `ReadGate`,
`Engine` or the handlers outside this package (checked against Core, SDK, pragmatiqu.io), so a patch.
Tests: a filtered set paged to exhaustion, a navigation collection, a `$batch` inner request, a
route-composed service. Docs: drop the warning in `query-options/pagination.md` **and the transitional warning in `core/recipes/excel-power-bi.md` § *Until the next laravelui5/odata release*** with the release (both noted 2026-09-23 when the GEO/Boost continuation closed).

**Why it is not only theoretical.** Excel and Power BI follow `@odata.nextLink` and fold editor steps
into `$filter`/`$select`; any set above the default page size (200) then loads rows the filter
excludes. The Excel/Power BI recipe (2026-09-18) carries an interim note until this ships.

## [ ] `OP06` Unsupported `$filter` constructs are silently dropped, widening the result set

Surfaced 2026-08-22 (docs/code drift audit of `docs/odata/`). Both translators end their dispatch in
a no-op default:

- `FilterToQuery::visitBinary` — arithmetic (`add`/`sub`/`mul`/`div`/`mod`) and `has` fall to
  `default => null`
- `FilterToQuery::visitFunctionCall` / `FilterToEloquent::visitFunctionCall` — every function except
  `contains`/`startswith`/`endswith` falls to `default => null`
- `FilterToQuery::visitLambda` — `any`/`all` return `null` (the Eloquent translator implements both)

The parser accepts all of them (`FilterParser::OPERATORS` carries 27 functions plus the arithmetic
and lambda operators), so the expression is valid, planned, and then **contributes no WHERE clause**.
`Products?$filter=year(created_at) eq 2026` answers `200 OK` with the whole collection.

A filter that silently doesn't filter is a data-exposure shape, not a missing feature: a caller who
believes they scoped a read gets rows they meant to exclude, and the honest-partial channel the read
authorizer uses (`sap-messages`) is not involved. It is also the one failure mode a client cannot
detect — an empty predicate looks exactly like a permissive one.

**Fix:** the translators should refuse what they cannot translate — throw `NotImplementedException`
(501) naming the construct, rather than returning `null`. That turns an invisible wrong answer into
a legible error, and it makes the supported set self-documenting: whatever 501s is what the docs
must list. If a softer landing is wanted for lambdas on the SQL path, `FilterToQuery` could record a
`sap-messages` warning through `ReadContext` instead — but never a bare drop.

Note the asymmetry to resolve alongside it: `FilterToEloquent` supports `any`/`all` (`whereHas` /
`whereDoesntHave`) and `FilterToQuery` does not, so the same URL behaves differently depending on
which resolver backs the set.

## [ ] `OP07` Generated vocabulary attributes emit invalid CSDL — `#[LineItem]` gives no columns, `Boolean="1"`

Surfaced 2026-09-17 in the docs SEO pass (`docs/ROADMAP.md`, `/odata/` → *Docs and code disagree*),
re-checked in code 2026-09-18. Two defects in the attributes `bin/generate.php` produces under
`src/Vocabularies/`:

- **Collections of records become collections of strings.** `#[LineItem([...])]`
  (`Ui/V1/LineItem.php`) maps every element to `new ConstantAnnotationValue('String', (string) $v)`.
  `UI.LineItem` is a collection of `UI.DataField` records, so a UI5 client finds no columns. The same
  string mapping sits in 55 generated attributes; each one whose term's element type is a record
  (DataField, FieldGroup data, SelectionFields paths …) is affected.
- **The kind `Boolean` is written verbatim.** 27 attributes build `ConstantAnnotationValue('Boolean', …)`,
  and `CsdlSerializer::KIND_TO_XML_ATTR` maps only `Integer` → `Int`, so the XML reads `Boolean="1"`
  where CSDL expects `Bool="true"` (e.g. `Aggregation/V1/ApplySupportedDefaults.php`).

The hand-built path works: `RecordAnnotationValue` with `PropertyValue`s and a `Bool` constant, as
`annotations/applying-annotations` shows since the SEO pass. The docs still present `#[LineItem]` as
working (`annotations/overview`, `applying-annotations`); **by the author's decision (2026-09-18)
they stay as they are until a first consumer relies on them**, so this fix is not urgent, but it has
to land before anyone does.

Fix at the source: the generator emits record-shaped values for record-typed collection elements and
the CSDL kind names (`Bool`, `Int`, `String`, `Path` …), plus `Boolean` → `Bool` in
`KIND_TO_XML_ATTR` as a guard. A `$metadata` test per affected term family (DataField collection,
boolean capability) keeps it fixed.

## [ ] `OP08` Should the package ship a filter-extraction helper for Tier-3 resolvers? — evaluation

Opened 2026-09-21 out of Rangliste Punkt 6. `resolvers/custom-resolvers` documented a trait
`App\OData\ExtractsFilterValues` with an `extractFilterParams()` helper "for common filter patterns".
It exists **nowhere** — not in the package, not in any host in the workspace, not in `pragmatiqu.io`.
The page was corrected on 2026-09-21 to show a real walk over the `FilterExpression` tree instead,
written as host code.

**The question this leaves.** Every Tier-3 resolver that cannot translate the whole expression tree
writes the same small walk: descend the `and` chain, match `property eq literal`, read the column off
the last `PropertyPathExpression` segment. That is a candidate for the package.

**The argument against, and it is strong.** A helper whose job is to *pick out the parts it
understands* blesses exactly the failure mode already queued as *Unsupported `$filter` constructs are
silently dropped, widening the result set*: a resolver that ignores what it did not extract answers a
`200` with more rows than the client asked for. If such a helper ships, it must **refuse** rather than
return a partial map — i.e. it is not an "extract" helper at all, it is a *restricted-filter parser*,
and its value is the refusal as much as the extraction.

**To evaluate:** whether the refusing shape generalises (which constructs belong in the supported set —
`eq` only? `eq`/`ne`/comparisons? `contains`?), whether it belongs beside `AbstractEntitySet` or as a
trait, and whether the real answer is simply "document the walk" — which is what the page does today,
and may be enough. An acceptable outcome is "no helper, and here is why".

## [ ] `OP09` `$search` passes `%` and `_` through to `LIKE` — a search term is a wildcard pattern

Found 2026-09-21 while documenting `query-options/search` (Rangliste Punkt 6). Both resolvers build the
clause the same way — `SqlEntitySetResolver::applySearch()` (`:116`) and
`EloquentEntitySetResolver::applySearch()` (`:414`), identical bodies:

```php
$term = trim($plan->search, '"\'');
$q->orWhere($col, 'LIKE', '%' . $term . '%');
```

The term is parameter-bound, so this is **not** an injection. It is a matching surprise: `%` and `_`
are `LIKE` metacharacters, so `$search=50%` matches every row whose text begins with `50`, and
`$search=a_b` matches `axb`. A user searching for a percentage or a snake_case identifier gets
silently wrong results — the worst shape, because the response looks successful.

**Fix.** Escape the metacharacters in the term before interpolating — `\`, `%` and `_` — and declare
the escape character on the clause (`LIKE ? ESCAPE '\\'`), which SQLite, MySQL and Postgres all
accept. One private helper, called from both resolvers; they are duplicates today and should stay in
step, so extracting the search clause into one shared place is the better shape than fixing it twice.

**Test.** Three cases per resolver: a term with `%`, one with `_`, one with a backslash.

**Adjacent, not part of this.** `trim($plan->search, '"\'')` strips *any* number of leading and
trailing quotes of either kind, so `"'foo'"` becomes `foo` and a term that legitimately ends in an
apostrophe loses it. Harmless in practice; mentioned so the fix does not re-introduce it.
`query-options/search` documents the current behaviour — including the wildcard leak — since
2026-09-21.

## [ ] `OP11` The service document ignores `includedInServiceDocument` and never lists function imports

Surfaced 2026-09-17 in the docs SEO pass, decided 2026-09-21 (**Code bewegt sich; Patch**).
`ServiceDocumentHandler::handle()` walks entity sets (`:32`) and singletons (`:38`) and emits every
one of them. Two things are missing:

- **The visibility flag is never read.** `EntitySet::isIncludedInServiceDocument()` (`:43`, default
  `true`) and `FunctionImport::isIncludedInServiceDocument()` (`:46`) exist and are wired through
  `EdmBuilder:271` — nothing asks them. A set that was deliberately taken out of the service document
  is listed anyway.
- **Function imports are absent.** `EntityContainerInterface::getFunctionImports()` (`:59`) is ready;
  there is no third loop. OData v4 puts entity sets, singletons, function imports and action imports
  into the service document, each subject to its `IncludedInServiceDocument`.

**Fix.** Filter the entity-set loop on the flag and add a loop over `getFunctionImports()` emitting
`'kind' => 'FunctionImport'`. Singletons stay unconditional — `Singleton` carries no such flag, and
`metadata/service-document` says exactly that. **`FunctionImport`'s default flips from `false` to
`true`** (author, 2026-09-21): a function import belongs to the service's front door unless someone
says otherwise, same as an entity set. Tests: one hidden set, one listed function import, one hidden
function import.

`metadata/service-document` already describes the target state per kind; the only thing that moves on
the page is the documented `FunctionImport` default.

## [ ] `OP12` `cachedMetadataXMLPath()` is declared, implemented and read by nothing — **finish specifying**

Surfaced 2026-09-17 in the docs SEO pass. The method sits on the public interface
(`Service/Contracts/ODataServiceInterface.php:45`) and on `ODataService:82`, and no caller exists in
the package or its tests: `$metadata` is serialized on every request. `metadata/metadata-document`
and `services/defining-a-service` present it as a mechanism.

**Decided 2026-09-21 (author): it gets implemented, not removed.** The target behaviour, sketched:

> `odata:cache` produces, beside the Edm class tree, the **finished `$metadata` XML**, and the
> serializer **streams that file**. The cold path is unchanged — nothing about a non-cached service
> moves.

That keeps the interface honest, removes a per-request serialization from the warm path, and is
**additive: Minor or Patch, never a Major.**

**Open before it can be built — this entry is a stub until then:**
- **Where does the XML go for a pure OData project?** For a Core project the answer is settled:
  `resources/ui5/Metadata.xml`, beside the app's other generated resources. A standalone OData
  service has no such tree.
- **That difference needs a lever** — an argument or an option on `odata:cache` (a target path, or a
  mode that says "Core layout" vs "standalone"). Which of the two, and what the default is, is
  unentschieden.
- How `cachedMetadataXMLPath()` and the cache writer agree on the path (who owns it: the service, the
  command, or config), and what happens when the file is missing or stale — fall back to
  serialization silently, or fail loud? The house answer elsewhere is loud, but the warm path must not
  break a deployment that forgot the command.

Until those are settled, the two doc pages keep describing the mechanism (docs-are-the-spec); they
must not describe the path or the command flag, because neither is decided.

## [ ] `OP14` Key literals are not validated — `Products(abc)` becomes `Products(0)`

Surfaced 2026-09-17 in the docs SEO pass, decided 2026-09-21 (**Code bewegt sich; Patch**).
`QueryPlanner::parseLiteralForEdmType()` (`:459`) casts raw: `(int) 'abc'` is `0`, `(float) 'abc'` is
`0.0`, and for `Edm.Boolean` everything that is not `'true'` is `false`. A nonsense key therefore
becomes a **valid query for the wrong key** — usually `404 entity_not_found`, and where a row with
`id = 0` exists, the wrong row. `query-options/resource-paths` promises `400 invalid_key`.

Same family as *Unsupported `$filter` constructs are silently dropped*: quietly wrong is worse than
loudly wrong.

**Fix.** Validate per type family before constructing the literal (`filter_var` for int/float/bool,
the existing guid handling for `Edm.Guid`) and throw `BadRequestException('invalid_key', …)` on
failure — the code the page names, in the same tone as `unknown_key_property` two methods above. One
test per family, plus the composite-key path.

## [ ] `OP15` Nested `$count` inside `$expand` is parsed and never emitted

Surfaced 2026-09-17 in the docs SEO pass, decided 2026-09-21 (**Code bewegt sich; Patch**).
`$expand=Items($count=true)` parses cleanly and reaches the plan — `QueryPlanner:617` sets
`count: $nestedOpts['count']` on the `ExpandItem` — and nothing ever reads that field.
`EloquentEntitySetResolver` implements `count()` for the top level only (`:167`). The response carries
no `Items@odata.count`, and the client is never told its question was dropped. Silent-drop family,
like the `$filter` entry above.

**Fix.** On the Eloquent path the lever exists and is cheap: `withCount()` on the relation, emitted as
`Nav@odata.count` beside the expanded collection. For SQL-backed custom entity sets there is no such
lever — there the honest answer is a loud `400`, not silence. Tests: one Eloquent expand with
`$count=true`, one custom set that refuses.

`query-options/select-and-expand` describes the target state; it must show nested `$count` as
supported once this lands, and must not be trimmed back in the meantime.

## [ ] `OP16` The announced `odata-error` trailer is not an HTTP trailer — it corrupts the body instead

Surfaced 2026-09-17 in the docs SEO pass, decided 2026-09-21 (**Code bewegt sich; Minor**).
While streaming, `ODataResponse::sendContent()` announces `trailer: odata-error` (`:33`). If a
`ProtocolException` is raised mid-stream, `sendContentStreamed()` echoes
`OData-error: {json}` **into the body** (`:46`), behind the bytes already sent. That is not a trailer
field: a real trailer needs chunked transfer encoding and fields *after* the body. Two consequences
for the client — the announced trailer never arrives, and the body is no longer valid JSON, so a
strict parser fails on a response that looks successful by status code.

**Fix (Minor, because the wire behaviour changes).** Either emit a genuine trailer — chunked encoding,
the field sent after the last chunk, which PHP's output layer makes awkward but not impossible — or
drop the `trailer` header and keep an honest in-body error marker that the docs describe as such. The
decision belongs to whoever builds it; what must not survive is a header promising something that
never comes.

**This is one of the two pages that may name the defect.** `advanced/error-handling` and
`advanced/configuration` describe the streaming error path **with its current flaw and its cure**, so
that a consumer who hits a truncated body knows what they are looking at and that `odata.streaming =
false` is the way around it today.

## [ ] `OP20` Morph relations are discovered as ordinary navigations — short-term cure

Surfaced 2026-09-17 in the docs SEO pass, decided 2026-09-21 (**Code bewegt sich; Patch**).
Relation discovery decides by `instanceof` (`ModelDiscovery.php:336-337`). In Laravel `MorphTo`
extends `BelongsTo` and `MorphToMany` extends `BelongsToMany` (likewise `MorphOne`/`MorphMany` against
`HasOne`/`HasMany`), so a polymorphic relation falls into the regular arm and is wired as a plain
navigation property. Its target is `get_class($result->getRelated())` — true for the instance it was
called on, not for the type. The only brake is that the target model must itself be discovered; where
it is, the schema is **silently wrong** for every row of a different morph type.

**Fix.** Explicit morph arms **before** the regular ones, skipping them — which is what
`services/model-discovery` says happens. One discovery test per morph shape.

## [ ] `OP21` Morph relations: is there a spec-conform way to expose them at all? — evaluation

Opened 2026-09-21 (author), the medium-term half of the entry above. The short-term cure skips morph
relations; that is correct and not satisfying. A polymorphic relation is a legitimate modelling tool,
and OData v4 has shapes that might carry it — a navigation property whose target is a **base entity
type** with derived types per morph target, or a derived-type cast in the path
(`/Comments(1)/Namespace.Post/…`), or simply an exposed morph key pair with no navigation at all.

**To evaluate:** whether any of these can be produced from an Eloquent `MorphTo` **without** asking
the developer to hand-write the entity type; what a UI5 v4 client actually does with a base-type
navigation; and whether the cost is worth it against the honest alternative — the developer models
the polymorphic edge explicitly in a custom entity set.

Outcome is a decision, not necessarily code: "morph relations stay out, here is why, here is what to
do instead" is an acceptable result and would then go into `services/model-discovery` as guidance.

## [ ] `OP29` The vocabulary generator has drifted from the committed classes — regenerate deliberately

Found 2026-10-07 while building `OP19` level 3. A full run of the generator into a scratch directory
differs from `src/Vocabularies/` in 311 files. Most of it was the generator's stale imports, which are
fixed now. What remains decides how a full regeneration has to be done:

- **Two classes are hand-written.** `Core.Description` (with an `APPLIES_TO` over all 14 targets) and
  `Common.Label` carry `#[Attribute(Attribute::TARGET_ALL)]`. The generator would overwrite both.
  Either it learns the `TARGET_ALL` case, or it skips a marked hand-written class.
- **Upstream added required parameters.** `Common.SideEffects` / `SideEffectsType` gained
  `sourceEntitiesInserted`, `…Updated` and `…Deleted`, each a required `array`, and the generator
  places them before the optional ones. Regenerating breaks every existing
  `#[SideEffects(...)]`. They need defaults (or a major).
- **Array-valued record properties are cast with `(string)`.** The same three properties come out as
  `new ConstantAnnotationValue('String', (string) $this->sourceEntitiesInserted)`, which yields the
  string `"Array"`. This is the record-shaped half of `OP07`.
- **New terms upstream:** `Common.ExpandAfterConcatSupported`, `PersonalData.RelatedDataCategoryID`,
  `UI.CardItem`, `UI.CardItemType`, plus some changed docblocks and AppliesTo targets.
- **Path forms** reach every other vocabulary only through this regeneration. Until then,
  `Path` works in `Measures`, `CodeList` and the three `Common` terms (`Text`, `UnitSpecificScale`,
  `UnitSpecificPrecision`) only.

**Fix.** Settle the first three, then run `php bin/generate.php` once over all vocabularies and
review the diff as a release of its own. Do it together with `OP07`, since both are the generator's
value shapes. Minor if the defaults land, otherwise major.

---

## Done

Shipped items live in [`CHANGELOG.md`](./CHANGELOG.md) under their version. This
section keeps the roadmap-level breadcrumb — the *why it was queued* — for items
that passed through Pending.

## [x] `OP25` The shipped `namespace` default is our own house namespace — and it disagrees with the code fallback (v3.1.0)

Surfaced 2026-09-18 alongside the same default in Core's `ui5:app` generator, entered 2026-09-20
(author: record it, discuss separately). `config.php:31` ships
`'namespace' => env('ODATA_NAMESPACE', 'io.pragmatiqu')`. A host that publishes the config and does
not think about it serves *our* vendor namespace in its own `$metadata`: every fully-qualified type
name, every entity-set reference, in the one document a client trusts. `ODataServiceRegistry.php:22`
disagrees with it — its in-code fallback is `com.example.odata` — so the value a host ends up with
depends on whether the config file was published.

**Decided 2026-09-21 (author): the default stays `io.pragmatiqu`** — in the config, and as the
in-code fallback. **Code bewegt sich; Patch.** Two things follow:

- `ODataServiceRegistry:22` stops inventing `com.example.odata`. There is exactly one default value
  in the package, and it is `io.pragmatiqu`.
- **The value is read from config in every case.** A host that publishes the config and edits it gets
  its own namespace; one that does not gets the same value the config file would have given it. No
  path may bypass `config('odata.namespace')`.

Note the deliberate difference to Core's `ui5:app`, which was decided the other way on the same day
(no default, loud failure — `core/ROADMAP.md`). The generator writes source code into a customer's
repository, where a wrong prefix is expensive to undo; the OData namespace is a runtime value a host
changes in one line of config. Same question, two answers, on purpose.

**Done 2026-10-07 (v3.1.0).** `ODataService::DEFAULT_NAMESPACE` is the one value: `config.php` uses it, and so do the default registry and `ODataService::namespace()` when a service declares none. A null config value falls back to it as well. Test: `tests/Service/NamespaceDefaultTest.php`. The two rows left the *Known gaps* table, and the two *planned* markers left `advanced/configuration`.

## [x] `OP22` `discoverCustomEntitySet()` builds resolvers with `new`, not the container (v3.1.0)

Surfaced 2026-09-17 in the docs SEO pass, decided 2026-09-21 (**Code bewegt sich; Patch**).
`applyCustomEntitySets()` instantiates every registered resolver with `new $resolverClass()`
(`ODataService.php:296`). A set with constructor dependencies dies while the schema is built.
`resolvers/custom-entity-sets` promises container resolution.

**Fix.** `app($resolverClass)` instead of `new`. Additive — a dependency-free set is built exactly as
before — and it is the expectation Core sets with `ExecutableInvoker`. One caveat to carry into the
fix: this is the **schema-build path**, so the dependency must be resolvable at that moment; a
resolver that needs a request is a different bug and should stay one. Test: a custom set with a bound
dependency.

**Done 2026-10-07 (v3.1.0).** All four instantiation sites in `ODataService` use `app()`. Found on the way: an `AbstractEntitySet` with its own constructor must call `parent::__construct()`, which wires the set as its own source. `resolvers/custom-entity-sets` says so now. Test: `tests/Service/CustomEntitySetContainerTest.php`.

## [x] `OP17` `immutable_date` maps to `Edm.DateTimeOffset` (v3.1.0)

Surfaced 2026-09-17 in the docs SEO pass, decided 2026-09-21 (**Code bewegt sich; Patch**).
`ModelDiscovery::mapCastType()` throws `'datetime', 'timestamp', 'immutable_date', 'immutable_datetime'`
into one arm (`:471`). Laravel's `immutable_date` is a pure date and belongs with `'date'` on the line
above (`:470`) → `Edm.Date`. One line, plus a discovery test with an `immutable_date` cast.
`services/model-discovery` already says `Edm.Date`.

**Done 2026-10-07 (v3.1.0).** Test: `tests/Service/Discovery/CastMappingTest.php`.

## [x] `OP13` The `version` config never reaches the `OData-Version` response header (v3.1.0)

Surfaced 2026-09-17 in the docs SEO pass, decided 2026-09-21 (**Code bewegt sich; Patch**).
`config.php:36` (`ODATA_VERSION`, default `'4.0'`) feeds exactly one consumer: the Edmx `Version`
attribute, via `ODataService:189`. Every handler writes the response header literally —
`EntitySetHandler:44`, `EntityHandler:61`, `ServiceDocumentHandler:46`, `MetadataHandler:25`,
`BatchHandler:70,122,143`, `PropertyValueHandler:61,75`, `SingletonHandler:35`,
`FunctionInvocationHandler:33`. `advanced/configuration:124` says the value is advertised "in
`$metadata` **and in the `OData-Version` response header**", and that is the state to reach.

**Fix.** One place that answers "which protocol version does this service speak", read from config,
used by both the Edmx writer and every handler — not eleven string literals. The natural shape is a
small accessor on the service (or a response helper the handlers already share), so that a future
4.01 is one config change rather than a grep. Test: a service configured to `4.01` answers `4.01` on
both surfaces.

**Done 2026-10-07 (v3.1.0).** `Protocol\Execution\ODataVersion::current()` is the one source, used by the Edmx builder and every handler's header, `$batch` parts included. Test: `tests/Protocol/Execution/ODataVersionTest.php`.

## [x] `OP10` `SqlQueryInterface`'s docblock promises consumers that do not exist (v3.1.0)

Found 2026-09-21 while repairing `resolvers/custom-entity-sets` (Rangliste Punkt 6). The interface
documents its consumers as:

> - `AbstractEntitySet` — OData custom entity sets
> - **Core artifact types (Report, AnalyticsSet, ValueHelp) via their own extensions**

The second line is stale twice over. There is **no consumer**: a grep across `core/src` and
`sdk-host/ui5/Sdk/src` finds the name only in `core/CHANGELOG.md`, in the April-2026 reset entry that
listed `SqlQueryInterface` among the contracts "still in flight" and pointed at Core's `PLAN.md` — the
plan that was **retired**, because the analytics substrate moved to the SDK and was redesigned there
(`core/ROADMAP.md` header). And two of the three named types, `AnalyticsSet` and `ValueHelp`, are
**SDK** artifact types, not Core's.

So the interface is real and useful — it is what makes `AbstractEntitySet` self-describing — but its
docblock advertises a shared contract that nobody shares yet.

**Fix.** Name the one consumer that exists, and describe the second as intent rather than fact: the
SDK's reporting and analytics layer is built on the same idea, and if it adopts this interface the
docblock says so then. The doc page was corrected on 2026-09-21 and already reads that way; this entry
is the code half.

**Done 2026-10-07 (v3.1.0).** Checked again: neither `core/src` nor the SDK uses the interface. The docblock names `AbstractEntitySet` and describes other layers as possible, not existing.

## [x] `OP24` Property-level discovery attributes require a declared PHP property — which shadows Eloquent's attribute bag (v3.1.0)

> **Decided 2026-10-07 (author): option three plus `useHidden`.** Column attributes
> (`#[ODataProperty]`, `#[ODataIgnore]`, vocabulary annotations) are written on a **hooked
> property** that delegates to the attribute bag, as `AnnotatedAirport` already does. On the class,
> `useHidden: true` takes the model's `$hidden` out of the entity type, which also removes the
> `$filter` question on hidden columns. Still to build: `useHidden`, the docs samples, and the
> idiom checked against mass assignment, `toArray()` and `isset()`.

Surfaced 2026-09-18 in the docs pass over `/odata/`, entered 2026-09-20 (author: record it, decide
separately). `ModelDiscovery` reads `#[ODataIgnore]`, `#[ODataProperty]` and every vocabulary
annotation off a **`ReflectionProperty`** — `$ref->hasProperty($colName)` gates all three
(`ModelDiscovery.php:230`, `:240`, `:254`, `:494`). For the attribute to be findable, the model must
declare a real PHP property named like the column. And that is exactly what an Eloquent model must
never do: a declared `public $internal_notes` shadows `__get`/`__set`, so the attribute bag is
bypassed — reads answer `null` instead of the column value, and writes land on the object property
and are dropped on `save()`. The documented usage teaches the breakage:
`services/model-discovery` shows `#[ODataIgnore] public $internal_notes;` and
`#[ODataProperty(name: 'FlightCode')] public $flight_number;`.

So the property-level half of the discovery API is unusable as written. The method-level half
(`#[ODataIgnore]` on a relation method, `#[ODataNavigation]`) is fine — methods shadow nothing.

**A dedicated session (author, 2026-09-21), and it settles `#[ODataProperty(nullable:)]` with it** —
both are defects of the same attribute surface, and the shape chosen here decides where `nullable`
is written. **Target: Minor or Patch**, depending on which shape wins.

**Not decided (author, 2026-09-20).** The shape is a class-level attribute; the open question is what
it carries:

- `useHidden: true` — everything in the model's `$hidden` stays out of the entity type. Reuses a list
  the model already keeps, and it closes a second hole the docs name on the same page: `$hidden`
  protects the *answer* but not the *question*, because a hidden column is still declared in
  `$metadata` and therefore filterable (`$filter=startswith(password,'$2y')`). Sourcing the ignore
  list from `$hidden` removes the column from the schema, and the question with it.
- `properties: ['internal_notes', …]` — an explicit list at the class, independent of `$hidden`.
- Both, or `$hidden` alone with no attribute at all.

There is a **third option the repository already runs**, and it keeps the attributes where they are:
declare the property with **PHP 8.4 property hooks** that delegate to the attribute bag —
`public string $code { get => $this->getAttribute('code'); set(string $v) => $this->setAttribute('code', $v); }`.
The property is virtual, so nothing is shadowed and the round-trip is intact. That is exactly how the
test fixture `tests-fixtures/Models/AnnotatedAirport.php` carries `#[Label]`, `#[Description]` and
`#[Hidden]`, and the package floor is PHP 8.4, so it is available everywhere. The docs show it
nowhere. If this is the answer, the fix is a documentation fix plus a check of the idiom against mass
assignment, `toArray()` and `isset()`.

Whatever is chosen, `#[ODataProperty]` and the annotation reader need the same treatment, or they
stay unusable for the same reason. The docs page must change with the code (two samples, plus the
`$hidden` note that currently ends in "mark it `#[ODataIgnore]`") — and **until the session has run,
`services/model-discovery` cannot state a target state for this half of the API**, because none is
decided. It is the one page in the tree that is knowingly left standing on an unresolved fork.

**Test coverage: none.** `#[ODataIgnore]`, `#[ODataProperty]` and `#[ODataNavigation]` have no test
at all — `ModelDiscoveryTest` covers only the class-level `#[ODataEntity]` override (`:240`). The
annotation tests use the hooked fixture, which is why the shadowing has never shown up in a run. A
test that writes and re-reads an ignored column *through the model* would catch it.

**Done 2026-10-07 (v3.1.0)** as decided. `#[ODataEntity(useHidden: true)]` leaves `$hidden` columns and
relations out (the key excepted), and is off by default. The hook idiom was checked against mass
assignment, `fill()`/`update()`, `toArray()`, `isset()`, dirty tracking and `save()`
(`tests/Service/Discovery/PropertyHooksAndHiddenTest.php`), and the check found a defect in the idiom
as we wrote it. The short form `set($v) => $this->setAttribute(…)` assigns the returned model to the
property, so a direct assignment throws a `TypeError`. Fixture, tests and docs now use the block form
with nullable types. The docs samples (`#[ODataIgnore]`, `#[ODataProperty]`, the `$hidden` warning)
state the target. `nullable:` was settled with `OP18` and `precision:`/`scale:` with `OP19`.

## [x] `OP23` `Edm.Binary` goes onto the wire unencoded (v3.1.0)

Surfaced 2026-09-17 in the docs SEO pass, decided 2026-09-21 (**Code bewegt sich; Patch**).
`RowCoercion` coerces two property kinds — temporal primitives and enums. `Edm.Binary` is not among
them, and discovery does produce it (`ModelDiscovery.php:451`: `blob`, `binary`, `varbinary`). Raw
bytes therefore reach `json_encode`, which fails on invalid UTF-8: the response does not come out
wrong, it does not come out at all. `getting-started/concepts` says base64.

**Fix.** A coercer for `Edm.Binary` in `RowCoercion::buildCoercers()` — **base64url without padding**,
which is what the OData v4 JSON format prescribes, not a plain `base64_encode`. Test with a blob
column carrying non-UTF-8 bytes.

**Done 2026-10-07 (v3.1.0).** Base64url without padding in `RowCoercion`, so on every path that
emits an entity. `/$value` answers the raw bytes as `application/octet-stream` and skips the JSON
coercion. Tests: `tests/Protocol/Execution/BinaryOnWireTest.php`, with non-UTF-8 bytes. Without the
coercer, two of the four fail.

## [x] `OP02` SQL-driver serialization emits raw DB scalars, not values coerced to the declared Edm type (v3.1.0)

Surfaced 2026-07-06 (`laravelui5/sdk` — `sdk-host` Partners `PartnerParametersEntitySet`) adding a
computed `writable_by_actor` column declared `EdmPrimitiveType::Boolean`. A custom entity set's rows
flow `AbstractEntitySet::query()` → `SqlEntitySetResolver` → `->get()` → `EntitySetHandler` (`:84`),
which echoes `json_encode($row)` on the **raw DB row**. Nothing coerces each property to its declared
Edm type, so the wire value is whatever the driver returned. MySQL/SQLite have no native boolean
(`TRUE`/`FALSE` are literals for `1`/`0`), so a `case when … then 1 else 0 end` column declared
`Edm.Boolean` serializes as JSON `1`, not `true`. The `columns()` Edm map drives the `$metadata`
schema (the contract the client reads) but **not** the runtime values — a declared-type-vs-emitted-value
split. A strict client (the UI5 v4 model) trusts the metadata, sees `1` for an `Edm.Boolean` property,
and rejects it (`"1" is of type number, expected boolean`). Same silent-wrong-value family as the cache
items — the schema promises one thing, the value delivers another.

The Eloquent path (`EloquentEntitySetResolver` + a model `'col' => 'boolean'` cast) already yields a
real PHP bool before encoding, so it serializes correctly; the raw `fromSub`/query-builder SQL path has
no such lever.

**Fix:** the SQL driver should coerce each selected property to its declared `EdmPrimitiveType` before
`json_encode` — `Boolean → (bool)`, `Int16/Int32/… → (int)`, `Decimal/Double/Single → (float)`,
preserving null. PHP-side, so it's cross-DB-safe (independent of the driver's return type) and closes the
gap for every SQL-backed custom set (boolean flags, numeric fidelity), not just this one column.

Workaround (in place downstream): model 0/1 flags as `EdmPrimitiveType::Int32` and coerce at the client
boundary (`{= !!${…} }` when binding to a boolean control property).

**Two more occurrences, 2026-09-14** — the same error text, reported from the browser against
`sdk-host`: `PartnerContactsExpand.is_primary` and `PartnerRelationshipsExpand.is_primary`. Both pass
their rows through untouched (`->map(fn ($row) => (array) $row)`), and the Details view binds
`visible="{is_primary}"` on a `sap.m.ObjectStatus` — which refuses the number for a boolean property
and logs once per row. Fixed there with the **other** workaround shape: keep the honest `Edm.Boolean`
declaration and cast in the row map (`'is_primary' => (bool) $row->is_primary`). That is the shape to
prefer downstream — it keeps `$metadata` truthful and leaves nothing for a client to un-lie — and it is
already the house style in the same folder (`PartnerGrantableAbilitiesExpand` casts every scalar).
**When this entry is fixed, those casts become redundant rather than wrong**, so they are safe to leave
until then. The count matters for the fix's case: three sets in one app were already affected, and the
cast is only ever remembered by whoever last hit the error.

**Done 2026-10-07 (v3.1.0).** In `RowCoercion`, so it holds on every path that emits an entity:
set, single entity, singleton, property value and `$expand`, on the SQL and the Eloquent driver
alike. Booleans from `1`/`0`/`"t"`/`"f"`, integers and numbers from driver strings. Unreadable values
pass unchanged. Under `IEEE754Compatible=true`, Int64 and Decimal stay strings (`OP27`). One existing
test had pinned the old pass-through (`'3.14'` for a Decimal) and now expects `3.14`. Tests:
`tests/Driver/Sql/DeclaredTypeOnWireTest.php` (the computed-flag case over HTTP) and the
`declared type over driver scalar` block in `RowCoercionTest`. The downstream casts in `sdk-host`
(`PartnerContactsExpand`, `PartnerRelationshipsExpand`, `PartnerGrantableAbilitiesExpand`) are
redundant now and can go when convenient.

## [x] `OP28` `odata:cache` is still lossy beyond annotations — complex types do not even load (v3.1.0)

Found 2026-10-07 while fixing `OP26`, **confirmed for complex types, the rest by reading.** The
warm path must reproduce the cold Edmx, and in these places it does not:

- **Complex types break the cache.** `writeEdmx()` registers them as `Types\X::instance()`, but the
  complex-type template has no `instance()`. A cached service with a complex type dies with
  `Call to undefined method …::instance()`. Discovery never produces complex types, so only
  hand-configured services are affected.
- **Type definitions are dropped.** The generated `Schema` gets no `typeDefinitions`, and a property
  typed by one falls back to `Edm.String` (`generateTypeCode()`).
- **Functions lose their shape.** `generateFunctionCode()` keeps name, return type and parameters
  only: `isBound`, `isComposable`, `returnsCollection`, `isReturnTypeNullable` and `entitySetPath` are
  lost, and parameters lose `isCollection`, `isNullable` and their facets. A non-primitive parameter
  or return type becomes `Edm.String` (`generateParamTypeCode()`).
- **Function imports** lose `entitySet` and `includedInServiceDocument` (this matters with `OP11`).
- **Singletons** lose their navigation property bindings.
- **Entity and complex types** lose `baseType`, `isAbstract` and `isOpen`. The templates return
  `null`/`false`.

**Fix.** One session that does for these what `OP26` did for annotations: generate each from the
cold object, and extend `AnnotationsCacheTest`'s warm-equals-cold check (or a sibling) with a model
that uses every feature. The parity test is the contract. Anything the serializer writes, the cache
must carry. Patch.

**Done 2026-10-07 (v3.1.0).** Every point above was fixed. On top of them, keys were referenced by
position instead of by name, and navigation properties lost containment and `OnDelete`. The serializer
also wrote `Nullable` twice on a parameter with facets and at all on a `TypeDefinition`. Tests:
`tests/Service/Cache/EdmxShapeCacheTest.php`. Against the previous writer, four of the five fail.

## [x] `OP27` `IEEE754Compatible=true` is ignored — `Edm.Decimal` and `Edm.Int64` go out as JSON numbers (v3.1.0)

Found 2026-10-07 by the code-list probe (`meta/specs/codelists-v1.0.md`, Nachtrag 2026-10-07). UI5's
V4 model sends `Accept: application/json;odata.metadata=minimal;IEEE754Compatible=true` on every
request. The format parameter asks the service to write `Edm.Int64` and `Edm.Decimal` as JSON
**strings**, because a JavaScript number cannot hold them exactly. The engine ignores the parameter:
`"amount":1234.5`, not `"amount":"1234.5"`, with or without it.

UI5 accepted the numbers in the probe, so nothing breaks visibly. The problem is precision. A
`decimal(19,6)` (the SDK's amounts, prices and quantities, Foundation D161) holds up to 19 significant
digits, and a JavaScript number about 15–17. Large amounts get rounded in the client without any
error. An `Int64` key above 2^53 is corrupted the same way.

**Fix.** Honour `IEEE754Compatible=true` from the `Accept` header (or `$format`): `Edm.Decimal` and
`Edm.Int64` are written as strings, `null` stays `null`. Without the parameter the output stays as it
is. The natural seat is the row coercion (`RowCoercion`), the same place as `OP02` and `OP23`; do all
three in one session. The response's `Content-Type` echoes `IEEE754Compatible=true` when it was
applied. Test: one decimal and one Int64 column, with and without the parameter; a decimal with 19
significant digits round-trips exactly.

**Done 2026-10-07 (v3.1.0).** `WireFormat::fromAccept()` reads the parameter from the request's
`Accept`, or from each `$batch` part's own headers, which the batch handler now parses. `RowCoercion`
writes Int64 and Decimal as exact strings and recurses into `$expand`. Singletons and property values
are coerced too, and the `Content-Type` echoes the parameter. `OP02` and `OP23` were done the same day. Tests:
`tests/Protocol/Execution/Ieee754CompatibleTest.php`.

## [x] `OP19` `discoverModel()` emits no type facets — a `decimal(19,6)` column becomes a bare `Edm.Decimal` (v3.1.0)

> **Status 2026-10-07: level 1 built (v3.1.0).** `Nullable`, `Precision`/`Scale` and `MaxLength`
> come from `Schema::getColumns()`, and `odata:cache` keeps them (`EdmxWriter` had dropped facets,
> the collection flag and the default value). Tests: `tests/Service/Discovery/ColumnFacetsTest.php`,
> including identical `$metadata` warm and cold. **Point 1 of the extension built the same day
> (v3.1.0):** `#[ODataProperty(precision:, scale:)]` and `ColumnFacetResolverInterface` (default
> `ColumnFacetsAsDeclared`, `bindIf`), order schema → resolver → attribute, illegal facets refused
> loud. The SDK's price/percentage resolver is SDK work. **Level 3 built the same day (v3.1.0):**
> `ODataService::annotateContainer()` (concrete `EdmBuilder::annotateContainer()`; the interface gets it
> with the next major), the `Path` value and path forms in the generator, the `CodeList` vocabulary,
> and the unpaged guarantee as a test (`tests/Service/CodeListHttpTest.php`, including warm = cold).
> Only `Measures`, `CodeList` and three `Common` terms were regenerated; the rest is `OP29`.

Surfaced 2026-10-01 in the SDK Foundation signing (`meta/specs/sdk-foundation-v1.0.md`, OP27 / D50),
**confirmed by reading, test still to write.** The serializer can emit every facet
(`Service/Serialization/CsdlSerializer.php:534-552`: `Nullable`, `MaxLength`, `Precision`, `Scale`
incl. `variable`), and `Edm\Property\Property` accepts `TypeFacetsInterface`. But discovery builds
each property with name, type and annotations only (`ModelDiscovery.php:258-262`); nothing in `src/`
constructs `TypeFacets` with a precision or scale. So a model column `decimal(19,6)` reaches
`$metadata` without `Precision`/`Scale`, a `varchar(255)` without `MaxLength`, and every property
without `Nullable` from the schema.

**Why it matters now.** The SDK stores amounts, prices, quantities and percentages with one fixed
scale (Foundation OP27) and relies on `$metadata` to describe it; UI5's `sap.ui.model.odata.type.Decimal`
reads `Scale` for formatting and input validation. A bare `Edm.Decimal` is formatted without a scale.

**Fix:** derive the facets from the column schema discovery already reads (`$column['type_name']`, the
type's precision/scale/length, nullability) and pass a `TypeFacets` to the `Property` constructor;
`#[ODataProperty]` overrides win (see the `nullable:` entry above — same surface, same session).
Additive. Test: a model with `decimal(19,6)`, `varchar(40)` and a nullable column; assert the three
facets in `$metadata`.

**Extended 2026-10-01 (Foundation D51) — three more things the SDK's code lists need from the engine:**

1. **Overriding a facet.** A unit price is stored as `decimal(19,6)` but must be announced with the
   installation's price decimals (`Scale="4"`), and a percentage likewise — UI5's `Decimal` type formats
   and validates input by `Scale`. The schema value is the default; an override wins: a `scale:` (and
   `precision:`) parameter on `#[ODataProperty]`, or a facet resolver the SDK binds, since the value is
   an installation fact, not a literal. Same session as the `nullable:` entry below.
2. **The `CodeList` vocabulary** (`com.sap.vocabularies.CodeList.v1`: `CurrencyCodes`, `UnitsOfMeasure`
   as `CodeListSource` with `Url` and `CollectionPath`; `StandardCode`), generated with the
   `VocabularyGenerator`. Container-level annotations are already serialized (`CsdlSerializer.php:154 ff.`);
   `Measures` and `Common.UnitSpecificScale` already exist.
3. **Code-list sets are never paged by the server.** UI5 loads a code list with
   `requestContexts(0, Infinity)` through its own shared model and caches it per session. Today
   server-driven paging applies only when a client sends `Prefer: odata.maxpagesize`
   (`EntitySetHandler.php:37`); that must stay so for these sets, and `$select` must work on them.

**Level 3 reshaped by the probe (2026-10-07).** The check D50 (5) asked for ran in `acme` with
OpenUI5 1.136.18 against odata 3.0.6 (findings: `meta/specs/codelists-v1.0.md`, Nachtrag
2026-10-07). The mechanism works against this engine: per-row formatting by the code's scale, texts,
standard codes. What it changes here:

- **Point 3 is no mechanism, only a guarantee.** UI5 sends `GET Set?$select=key,scale,text,standard`,
  with no `$top`/`$skip`/`$count` and no `Prefer`, and the engine already answers unpaged. What remains
  is a test that pins this down: no `Prefer` → no `@odata.nextLink`, `$select` honoured.
- **Container annotations need a builder API.** The serializer writes them inline and external
  (UI5 reads both), but `EdmBuilder` offers no way to set them. The probe needed a decorator that
  rebuilds the frozen `EntityContainer`. Fix: something like `annotateContainer(AnnotationInterface ...)`
  on `EdmBuilderInterface`. Additive.
- **Path-valued annotations.** Every annotation the mechanism needs is a `Path`:
  `Measures.ISOCurrency` / `Measures.Unit` on the business property, `Common.UnitSpecificScale` /
  `Common.Text` / `CodeList.StandardCode` on the code-list key. The generated classes emit constants
  only (`ISOCurrency` takes a `string` and writes `String="…"`). The probe used a generic
  `Annotation` with `ConstantAnnotationValue('Path', …)`, which the serializer writes correctly.
  Fix in the `VocabularyGenerator`: terms whose type allows a path get a path form.
- **The `CodeList` vocabulary** (point 2) as planned: `CurrencyCodes`, `UnitsOfMeasure`
  (`CodeListSource`: `Url`, `CollectionPath`), `StandardCode`. The `Url` is **static** (relative,
  any `@version` resolves), so no facet resolver or installation value is involved.
- **Prerequisite: `OP26`** (done 2026-10-07, v3.1.0). The warm path used to drop every annotation,
  the container's included.

## [x] `OP26` `odata:cache` drops every vocabulary annotation — the warm `$metadata` carries none (v3.1.0)

Found 2026-10-07 while building `OP19` level 1, **confirmed by reading, test still to write.**
`EdmxWriter` generates each entity type, complex type and entity set with `$this->annotations = []`
(`src/Service/Cache/EdmxWriter.php`, the three class templates), and each property without its
annotations. Discovery reads `#[Label]`, `#[LineItem]`, `#[SelectionFields]` and the rest onto the
cold schema. A cached service serves none of them. Production runs cached.

Same family as the 3.0.3 enum fix and the facets in 3.1.0: the cached and the cold schema disagree,
silently. **It also blocks `OP19` level 3:** the container annotations (`CodeList.CurrencyCodes`,
`UnitsOfMeasure`) and the `Measures` paths are annotations too, so a cached installation would serve
none of them. Confirmed as a dependency by the code-list probe of 2026-10-07. The parity test in `ColumnFacetsTest` uses a model with no annotations for exactly this
reason; `AnnotatedAirport` would fail it.

**Fix.** Generate the annotations as code: `Annotation` with term, qualifier and value, the value
recursive over `ConstantAnnotationValue`, `RecordAnnotationValue` (`PropertyValue`s) and
`CollectionAnnotationValue`. Typed vocabulary classes (`TypedAnnotationTrait`) need checking:
either they are regenerated as their own class or flattened to the generic `Annotation`, provided the
serializer output stays identical. Test: warm and cold `$metadata` identical for `AnnotatedAirport`.

**Done 2026-10-07 (v3.1.0).** The scope turned out wider than the entry: the writer dropped the
annotations of **every** element the serializer annotates (container, schema, entity and complex
types, properties, navigation properties, entity sets, singletons, function imports, functions,
parameters, enum types and members) and the `edmx:Reference`s. The generated classes also overrode
`getAnnotation()` with `return null`. `EdmxWriter` now generates all of them as generic `Annotation`
code (constant, record and collection values, recursively). Literals go through `var_export`, because
`addslashes` would keep a `"` as `\"` in a single-quoted literal. Tests:
`tests/Service/Cache/AnnotationsCacheTest.php`, with identical `$metadata` warm and cold for a model
annotated everywhere and for the discovered `AnnotatedAirport`. Against the old writer all four fail.

## [x] `OP18` `#[ODataProperty(nullable:)]` is declared and never read (v3.1.0)

Surfaced 2026-09-17 in the docs SEO pass, decided 2026-09-21 (**Code bewegt sich; Patch**).
The attribute carries the parameter (`Service/Discovery/Attributes/ODataProperty.php:15`); discovery
reads only `->type` and `->name` (`ModelDiscovery.php:240 ff.`). Setting it changes nothing in the
schema.

**Fix: read it, do not remove it.** Removing a parameter from a public attribute would be a contract
break; reading it is additive and makes the declaration true — pass it through to the `Property`
constructor, with the discovered column nullability as the default when the attribute is silent.

**Belongs to the same session as the property-attribute entry below** (author, 2026-09-21): both are
defects of the same attribute surface, and the decision there may move where the parameter is written.

**Done 2026-10-07 (v3.1.0)** together with level 1 of `OP19`: the attribute overrides the column's
nullability, which discovery now reads as the default. The `OP24` decision (attributes on a hooked
property) keeps the parameter where it was.

## [x] `odata:cache` writes (and first deletes) `Edm/` directories inside `vendor/` (v3.0.3)

Surfaced 2026-08-19 in `pragmatiqu.io`. Same root cause as *EDM cache is keyed by class
location* (still open, under Pending) — but this is the write side, and it reaches outside the
application.

`ResolvesServices::resolveServices()` takes *every* registry service with no regard for where
its class lives, and `CacheCommand` derives the output path as
`dirname($reflected->getFileName()) . '/Edm'`. For a service that ships in a package, that path
is inside `vendor/`. One run in this host produced:

    vendor/laravelui5/auth/src/Edm/
    vendor/laravelui5/sdk/Partners/src/Edm/
    vendor/laravelui5/sdk/Settings/src/Edm/
    vendor/laravelui5/sdk/Launchpad/src/Edm/

**None of these are shipped.** Verified against the source repos: `laravelui5-sdk` and
`laravelui5-auth` track zero files under any `Edm/` path. Every such directory in `vendor/` was
written by a local `odata:cache`.

**Why it must not happen.**

1. **It contradicts the command's own contract.** `odata:cache` refuses to run in production
   with *"The generated Edm/ cache is committed to version control and deployed as-is."* For a
   `vendor/` path that is impossible — nothing there is committed. The dev machine therefore
   runs a **warm** path that production never has, and any defect that only shows on the cold
   path is invisible during development. A cache that changes behaviour only on the developer's
   machine is the wrong way round.
2. **`deleteDirectory` runs first.** A run that throws partway leaves a package's cache deleted.
   Combined with the empty-ResolverMap defect above, this host ended up with 25 silently
   unbound entity sets in `laravelui5/sdk`, which took out 103 feature tests. The only recovery
   was `rm -rf vendor/laravelui5/sdk && composer install` — nothing in the error message points
   there, because the error names the *entity sets*, not the package.
3. **`composer install` silently reverts it.** The artifact is invisible to git, so the state a
   developer debugs is not reproducible for anyone else and disappears on the next update.

**Shape of a fix.** Only write caches for services the application owns: skip any service whose
class file resolves inside the composer vendor directory (or, equivalently, require the output
path to be under `base_path()`), and say so in the run output rather than skipping silently.
Packages that want a shipped cache should generate it in **their own** build, committed to their
own repo — not in a consumer's `vendor/`.

**Shipped 3.0.3.** `ResolvesServices::resolveServices()` drops any service whose class file
sits under the composer vendor directory — both commands inherit it, and each skip is named in
the output. See `CHANGELOG.md` v3.0.3.

## [x] `odata:cache` writes an EMPTY ResolverMap for registry-cached apps — silently disables every entity set (v3.0.3)

Surfaced 2026-08-19 in `pragmatiqu.io` while adding a third entity set to the Sales app.

**Symptom.** Running `odata:cache` rewrites `Edm/ResolverMap.php` for the *host* apps
(`Pragmatiqu\Sales\SalesApp`, `Pragmatiqu\Portal\PortalApp`) as an empty map. Nothing
fails at write time — the command reports `Cached: …` and `OData schema cached successfully.`
The damage shows up on the next request or command as

    RuntimeException: The following entity sets have no resolver bound: Prospects, Customers.

An empty cached ResolverMap **wins over the runtime bindings**, so every set of that service
goes dark. In the host this took out 103 feature tests until the file was restored from git.

**Why the vendor apps escaped.** In the same run, `LaravelUi5\Sdk\Partners\PartnersApp` and
friends got *correct* maps (25 bindings). The difference is where the service instance comes
from: the vendor apps are constructed cold in-process, so `configure()` runs and
`discoverCustomEntitySet()` populates the bindings. The host apps are handed back by the
**ui5 registry cache** (`bootstrap/cache/ui5.php`), whose instances never ran `configure()` —
`$service->resolverMap()` is therefore empty, and `ResolverMapWriter` faithfully writes that.

**Second-order damage.** `CacheCommand` calls `$fs->deleteDirectory($outputDir)` *before*
building, and iterates the whole registry, so a throw partway leaves earlier services rewritten
and later ones with **no** cache at all. Where that lands in `vendor/` it is worse — see the
next item.

**What it makes impossible today.** A newly registered entity set cannot be cached: the
generator either writes an empty map or aborts, and `EdmxLoader` short-circuits the runtime
builder whenever `Edm/Edmx.php` exists — so the new set is invisible to the served schema
while the old cache stands.

**Shape of a fix.** (a) `resolverMap()` must be derived from the same pass that produced the
Edmx, not from whatever the instance happens to carry; (b) refuse to write an empty map when
the schema declares entity sets — fail loud instead of writing a booby-trapped cache;
(c) build every service *before* deleting any cache directory, so a partial run cannot leave
the tree worse than it found it.

### Recognising it, and getting back

The error names entity sets, never the file that broke them — which is why the first hour goes
into the wrong place. Two greps settle it:

```bash
# Any generated map that declares no bindings is a live booby trap.
grep -L 'Binding(' $(find . vendor -path '*/Edm/ResolverMap.php')

# Anything under vendor/ is generated, never shipped — the packages track no Edm/ at all.
find vendor -type d -name Edm
```

Recovery, in this order:

1. **Host apps** — `git checkout -- <app>/src/Edm`. The committed cache is the good one.
2. **Packages** — `rm -rf vendor/<pkg> && composer install`. Nothing under `vendor/*/Edm` is
   version-controlled, so there is nothing to restore; removing it is the repair, and the cold
   path takes over.
3. **Registry cache** — `bootstrap/cache/ui5.php` participates (see *Why the vendor apps
   escaped*). Regenerate it with `ui5:cache` **only after** step 1 and 2, because `ui5:cache`
   itself builds schemas and will abort on a broken map.

Until the fix lands, the working rule for a consumer is blunt: **do not run `odata:cache`.** A
newly registered entity set has to be added to the committed cache by hand — four files:
`Edm/Types/<Type>.php`, `Edm/Entities/<Set>.php`, plus one entry each in `Edm/Edmx.php` and
`Edm/ResolverMap.php`. Mechanical, and it keeps the served schema honest until the generator
can be trusted again.

**Shipped 3.0.3, completed in 3.0.4.** `resolverMap()` is built from `configure()`
unconditionally, out of the same pass that produces the Edmx (`buildFromConfigure()`), with the
accumulators reset so the build is repeatable. 3.0.3 left one half undone — `CacheCommand` still
took the *Edmx* from `schema()`, which memoises and prefers the warm path, so a stale schema
survived every regeneration; 3.0.4 added `buildForCache()` and takes both artefacts from the one
cold pass. `odata:cache` additionally builds every service before deleting any cache
directory, so a failure part-way leaves the tree as it found it. See `CHANGELOG.md` v3.0.3.

## [x] `EdmxWriter` flattens enum-typed properties to `Edm.String` — cached and cold schemas disagree (v3.0.3)

Noticed 2026-08-19 in `pragmatiqu.io` while hand-maintaining the Sales cache.

A columnar entity set may declare a backed-enum class-string instead of an `EdmPrimitiveType`;
`AbstractEntitySet::entityType()` turns that into `EnumType::fromBackedEnum($namespace, $type)`.
The **cached** schema does not: every such property comes back as

    new Property('tier', new PrimitiveType(EdmPrimitiveType::String)),

and no `EnumType` is declared in the generated `Edmx.php` at all. Checked across both host apps
(`Pragmatiqu\Sales\Edm\Types\Customer`, `Pragmatiqu\Portal\Edm\Types\MyLicense`) — consistent,
so it is the writer, not a one-off.

**Why it matters.** The warm and cold paths then serve *different* `$metadata` for the same
service. A client that binds an enum property — an `sap.ui.mdc` field with a type map, or
anything relying on `Edm.EnumType` member names — behaves differently depending on whether a
cache happens to exist. That is the same silent-divergence family as the collision item below,
and it defeats the point of declaring an enum column at all: the entity set deliberately
declares the enum class-string *so that* the engine emits `Edm.EnumType`.

**Shape of a fix.** `EdmxWriter` must emit the enum types into the schema's `enumTypes` and
reference them from the property, mirroring what `entityType()` builds. A regression test that
round-trips a set with one enum column through writer → loader and compares the resulting
`$metadata` to the cold path would have caught it.

**Shipped 3.0.3.** `generateTypeCode()` gained an `EnumTypeInterface` branch and the generated
`Schema` now carries `enumTypes`; three round-trip regressions guard it, including one that
asserts warm and cold coerce a backing int to the same wire value. See `CHANGELOG.md` v3.0.3.

## [x] `odata:cache` / `odata:clear` learn `--class` for route-composed services; collisions fail loud (v1.0.7)

`odata:cache` discovered services only through the `ODataServiceRegistryInterface`, so a service
served via `OData::forService()` on its own route (deliberately outside the registry — 1.0.6,
e.g. timesheet's `App\Excel\ExcelService`) could not be pre-cached and always ran the cold path.
Both commands now take `--class=FQCN1,FQCN2` — cached/cleared in addition to the registry,
validated, deduped. Plus a fail-loud pre-pass on `odata:cache` for cache-dir collisions (the
write side of the item still in Pending). See `CHANGELOG.md` v1.0.7.

## [x] `odata:cache` generates entity-set classes that collide with their type import (v1.0.5)

Surfaced in production (2026-06-04, `pragmatiqu.io`): the Portal cache fataled at
autoload on `MyDraft` / `MyOrganization` / `MyPortalState` — sets whose names equal
their entity types — because `EdmxWriter::writeEntitySet()` emitted both a
`use {ns}\Types\{Name};` import and a `class {Name}` declaration in the same file.
Pluralized sets escaped by luck, not design. Fixed by referencing the type by FQN
and dropping the import; regression-guarded by an EdmxWriter test that writes a
set-name-equals-type-name EDM and loads the generated class. See `CHANGELOG.md` v1.0.5.
