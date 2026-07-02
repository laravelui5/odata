<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Planning;

use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Exception\BadRequestException;
use LaravelUi5\OData\Http\ODataRequest;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;

final readonly class QueryPlanner
{
    /**
     * Produce a fully validated, schema-resolved QueryPlan from a request.
     *
     * @throws BadRequestException on unknown entity set, unknown property, or invalid key.
     */
    public function plan(ODataRequest $request, RuntimeSchemaInterface $schema): QueryPlan
    {
        $segments = $request->pathSegments();

        if ($segments === []) {
            return new ServiceDocumentQueryPlan($schema->getEdmx());
        }

        $first = $segments[0];

        if ($first === '$metadata') {
            return new MetadataQueryPlan($schema->getEdmx());
        }

        if ($first === '$batch') {
            return new BatchQueryPlan([], false);
        }

        // Extract entity-set name and optional key from the first segment.
        // Matches: "Products", "Products(1)", "Products(id=1)", "Products(id=1,code='A')"
        if (!preg_match('/^([^(]+)(?:\((.*)\))?$/', $first, $m)) {
            throw new BadRequestException('invalid_path', "Invalid path segment: {$first}");
        }

        $setName   = $m[1];
        $keyString = $m[2] ?? null;

        $container = $schema->getEdmx()->getEntityContainer();
        $entitySet = $container->getEntitySet($setName);

        if ($entitySet === null) {
            $funcImport = $container->getFunctionImport($setName);
            if ($funcImport !== null) {
                $params = $this->parseFunctionParameters($funcImport, $keyString);
                return new FunctionInvocationPlan($funcImport, $params);
            }

            $singleton = $container->getSingleton($setName);
            if ($singleton !== null) {
                return new SingletonQueryPlan(
                    $singleton,
                    $this->parseSelectList($request->select, null, $singleton->getEntityType()),
                );
            }

            throw new BadRequestException('unknown_entity_set', "Unknown entity set: {$setName}");
        }

        if ($keyString !== null && count($segments) > 1) {
            // Navigation path: /Flights(1)/passengers or /Flights(1)/passengers(5)
            return $this->buildNavigationPlan($entitySet, $keyString, array_slice($segments, 1), $request, $schema);
        }

        if ($keyString !== null) {
            return $this->buildEntityQueryPlan($entitySet, $keyString, $request, $schema);
        }

        return $this->buildEntitySetQueryPlan($entitySet, $request, $schema);
    }

    // -------------------------------------------------------------------------
    // Entity query (single entity by key)
    // -------------------------------------------------------------------------

    private function buildEntityQueryPlan(
        EntitySetInterface $entitySet,
        string             $keyString,
        ODataRequest       $request,
        RuntimeSchemaInterface $schema,
    ): EntityQueryPlan {
        $key    = $this->parseKeyExpression($keyString, $entitySet);
        $select = $this->parseSelectList($request->select, $entitySet);
        $expand = $this->parseExpandList($request->expand, $entitySet, $schema);

        return new EntityQueryPlan(
            target: $entitySet,
            key:    $key,
            select: $select,
            expand: $expand,
        );
    }

    // -------------------------------------------------------------------------
    // Entity set query (collection)
    // -------------------------------------------------------------------------

    private function buildEntitySetQueryPlan(
        EntitySetInterface     $entitySet,
        ODataRequest           $request,
        RuntimeSchemaInterface $schema,
    ): EntitySetQueryPlan {
        $entityType = $entitySet->getEntityType();

        $filter  = $request->filter !== null
            ? $this->parseFilterExpression($request->filter, $entityType)
            : null;

        $select  = $this->parseSelectList($request->select, $entitySet);
        $orderBy = $this->parseOrderByList($request->orderBy, $entityType);
        $expand  = $this->parseExpandList($request->expand, $entitySet, $schema);

        return new EntitySetQueryPlan(
            target:      $entitySet,
            filter:      $filter,
            select:      $select,
            expand:      $expand,
            orderBy:     $orderBy,
            top:         $request->top,
            skip:        $request->skip,
            skipToken:   $request->skipToken,
            count:       $request->count,
            search:      $request->search,
            compute:     $this->parseCompute($request->compute),
            maxPageSize: $request->maxPageSize,
            customQueryOptions: $request->customQueryOptions,
        );
    }

    // -------------------------------------------------------------------------
    // Navigation path segments (e.g. /Flights(1)/passengers)
    // -------------------------------------------------------------------------

    /**
     * Build a plan for navigation paths: /EntitySet(key)/navProperty[/navProperty(key)...]
     *
     * Resolves the navigation chain to a target entity set and injects an
     * implicit filter on the parent's key. For example, /Flights(1)/passengers
     * becomes an EntitySetQueryPlan on the Passengers set with filter flight_id eq 1.
     *
     * Multi-segment navigation (e.g. /Projects(1)/customer/contact_customer) is
     * handled by walking intermediate single-entity segments and building a
     * NavigationAnchor that the resolver evaluates at execution time.
     *
     * @param list<string> $remainingSegments  Path segments after the first (key) segment.
     */
    private function buildNavigationPlan(
        EntitySetInterface     $parentSet,
        string                 $parentKeyString,
        array                  $remainingSegments,
        ODataRequest           $request,
        RuntimeSchemaInterface $schema,
    ): QueryPlan {
        $rootSet   = $parentSet;
        $rootKey   = $this->parseKeyExpression($parentKeyString, $parentSet);
        $container = $schema->getEdmx()->getEntityContainer();

        $currentSet  = $parentSet;
        $currentType = $parentSet->getEntityType();
        $currentKey  = $rootKey;
        $anchorSteps = [];

        $segmentCount = count($remainingSegments);

        for ($i = 0; $i < $segmentCount; $i++) {
            $isLast     = ($i === $segmentCount - 1);
            $navSegment = $remainingSegments[$i];

            if (!preg_match('/^([^(]+)(?:\((.*)\))?$/', $navSegment, $nm)) {
                throw new BadRequestException('invalid_path', "Invalid navigation segment: {$navSegment}");
            }

            $navName = $nm[1];
            $navKey  = $nm[2] ?? null;

            // Check for structural property access: /Flights(1)/origin
            // or /Flights(1)/origin/$value (structural property followed by $value).
            if ($navName !== '$value') {
                $structProp = $currentType->getProperty($navName);
                if ($structProp !== null) {
                    $rawValue = isset($remainingSegments[$i + 1]) && $remainingSegments[$i + 1] === '$value';
                    return new PropertyValuePlan(
                        target:   $currentSet,
                        key:      $currentKey,
                        property: $structProp,
                        rawValue: $rawValue,
                    );
                }
            }

            if ($navName === '$value') {
                throw new BadRequestException('invalid_path', '$value must follow a structural property');
            }

            $navProp = $currentType->getNavigationProperty($navName);
            if ($navProp === null) {
                throw new BadRequestException(
                    'unknown_navigation_property',
                    sprintf('Unknown property or navigation "%s" on entity type "%s"', $navName, $currentType->getName())
                );
            }

            $binding = $currentSet->getNavigationPropertyBinding($navName);
            if ($binding === null) {
                throw new BadRequestException(
                    'unbound_navigation_property',
                    sprintf('No navigation property binding for "%s" on entity set "%s"', $navName, $currentSet->getName())
                );
            }

            $targetSet = $container->getEntitySet($binding->getTarget());
            if ($targetSet === null) {
                throw new BadRequestException(
                    'unknown_target_set',
                    sprintf('Target entity set "%s" not found', $binding->getTarget())
                );
            }

            if ($isLast) {
                // Final segment — build the query plan.
                return $this->buildFinalNavigationPlan(
                    $currentSet, $currentKey, $navProp, $navKey, $targetSet,
                    $anchorSteps, $rootSet, $rootKey, $request, $schema,
                );
            }

            // Intermediate segment — must resolve to a single entity.
            // A collection navigation in the middle of a path requires a key.
            if ($navProp->isCollection() && $navKey === null) {
                throw new BadRequestException(
                    'invalid_path',
                    sprintf('Navigation "%s" is a collection; a key is required in the middle of a path', $navName)
                );
            }

            // Advance to the target entity set for the next iteration.
            $anchorSteps[] = $navName;
            $currentSet    = $targetSet;
            $currentType   = $targetSet->getEntityType();

            if ($navKey !== null) {
                // Collection with key: /Flights(1)/passengers(5)/bookings
                // Reset the anchor — the keyed entity becomes the new root.
                $rootSet     = $targetSet;
                $rootKey     = $this->parseKeyExpression($navKey, $targetSet);
                $currentKey  = $rootKey;
                $anchorSteps = [];
            }
        }

        // Should never reach here — the loop always returns on the last segment.
        throw new BadRequestException('invalid_path', 'Empty navigation path');
    }

    /**
     * Build the final query plan for a navigation path's last segment.
     *
     * When anchorSteps is non-empty, the plan includes a NavigationAnchor
     * so the resolver can walk intermediate single-entity navigations at
     * execution time to determine the parent entity.
     */
    private function buildFinalNavigationPlan(
        EntitySetInterface     $parentSet,
        KeyExpression          $parentKey,
        NavigationPropertyInterface $navProp,
        ?string                $navKey,
        EntitySetInterface     $targetSet,
        array                  $anchorSteps,
        EntitySetInterface     $rootSet,
        KeyExpression          $rootKey,
        ODataRequest           $request,
        RuntimeSchemaInterface $schema,
    ): QueryPlan {
        $anchor = $anchorSteps !== []
            ? new NavigationAnchor($rootSet, $rootKey, $anchorSteps, $navProp->getName())
            : null;

        if ($navKey !== null) {
            $targetKeyExpr = $this->parseKeyExpression($navKey, $targetSet);
            $select = $this->parseSelectList($request->select, $targetSet);
            $expand = $this->parseExpandList($request->expand, $targetSet, $schema);

            return new EntityQueryPlan(
                target: $targetSet,
                key:    $targetKeyExpr,
                select: $select,
                expand: $expand,
                anchor: $anchor,
            );
        }

        // Build implicit filter on the parent's key (only when no anchor).
        // When an anchor is present, the resolver builds the FK filter at
        // execution time after resolving the intermediate navigation chain.
        $targetType = $targetSet->getEntityType();
        $userFilter = $request->filter !== null
            ? $this->parseFilterExpression($request->filter, $targetType)
            : null;

        if ($anchor === null) {
            $constraints = $navProp->getReferentialConstraints();

            if ($constraints === [] && $navProp->isCollection()) {
                // BelongsToMany: no referential constraints and collection-valued.
                // Cannot build a direct FK filter because the FK lives on the pivot
                // table, not the target table.  Force an anchor so the resolver uses
                // Eloquent's relationship query builder (which joins through the pivot).
                $anchor = new NavigationAnchor($rootSet, $rootKey, [], $navProp->getName());
                $combinedFilter = $userFilter;
            } else {
                $implicitFilter = $this->buildParentKeyFilter($parentKey, $constraints, $parentSet);
                $combinedFilter = $userFilter !== null
                    ? new BinaryExpression($implicitFilter, BinaryOperator::And, $userFilter)
                    : $implicitFilter;
            }
        } else {
            $combinedFilter = $userFilter;
        }

        $select  = $this->parseSelectList($request->select, $targetSet);
        $orderBy = $this->parseOrderByList($request->orderBy, $targetType);
        $expand  = $this->parseExpandList($request->expand, $targetSet, $schema);

        return new EntitySetQueryPlan(
            target:    $targetSet,
            filter:    $combinedFilter,
            select:    $select,
            expand:    $expand,
            orderBy:   $orderBy,
            top:       $request->top,
            skip:      $request->skip,
            skipToken: $request->skipToken,
            count:     $request->count,
            anchor:    $anchor,
        );
    }

    /**
     * Build a FilterExpression that constrains the target set by the parent's key.
     *
     * Uses referential constraints if declared, otherwise falls back to convention:
     * the FK column is the lowercase parent entity set name (singular) + '_id'.
     *
     * @param array<string, string> $constraints  dependent → principal property names
     */
    private function buildParentKeyFilter(
        KeyExpression $parentKey,
        array         $constraints,
        EntitySetInterface $parentSet,
    ): Expression\FilterExpression {
        // If referential constraints are declared, use the first one.
        if ($constraints !== []) {
            $dependentPropName = array_key_first($constraints);
            $principalPropName = $constraints[$dependentPropName];

            $parentKeyValue = $parentKey->values[$principalPropName]
                ?? array_values($parentKey->values)[0];

            return new BinaryExpression(
                new PropertyPathExpression([
                    new \LaravelUi5\OData\Edm\Property\Property(
                        $dependentPropName,
                        new \LaravelUi5\OData\Edm\Type\PrimitiveType(
                            \LaravelUi5\OData\Edm\EdmPrimitiveType::Int32
                        )
                    ),
                ]),
                BinaryOperator::Eq,
                new LiteralExpression($parentKeyValue->value, $parentKeyValue->edmType),
            );
        }

        // Convention: parent set "Flights" → FK "flight_id", key value from parentKey.
        $parentName = rtrim($parentSet->getName(), 's'); // naive singularization
        $fkColumn   = strtolower($parentName) . '_id';
        $keyValue   = array_values($parentKey->values)[0];

        return new BinaryExpression(
            new PropertyPathExpression([
                new \LaravelUi5\OData\Edm\Property\Property(
                    $fkColumn,
                    new \LaravelUi5\OData\Edm\Type\PrimitiveType(
                        \LaravelUi5\OData\Edm\EdmPrimitiveType::Int32
                    )
                ),
            ]),
            BinaryOperator::Eq,
            new LiteralExpression($keyValue->value, $keyValue->edmType),
        );
    }

    // -------------------------------------------------------------------------
    // Key parsing
    // -------------------------------------------------------------------------

    private function parseKeyExpression(string $keyString, EntitySetInterface $entitySet): KeyExpression
    {
        $entityType   = $entitySet->getEntityType();
        $keyProperties = $entityType->getKey();

        if (str_contains($keyString, '=')) {
            // Named-key syntax: id=1,code='A'
            $values = [];
            foreach (array_filter(array_map('trim', explode(',', $keyString))) as $pair) {
                [$name, $rawValue] = array_map('trim', explode('=', $pair, 2));
                $keyProp = $this->findKeyProperty($name, $keyProperties);
                $values[$name] = $this->parseLiteralForEdmType(
                    $rawValue,
                    $keyProp->getType()->getQualifiedName()
                );
            }
            return new KeyExpression($values);
        }

        // Positional key: must have exactly one key property
        if (count($keyProperties) !== 1) {
            throw new BadRequestException(
                'invalid_key',
                'Composite key requires named-key syntax (property=value,...)'
            );
        }

        $keyProp = $keyProperties[0];
        return new KeyExpression([
            $keyProp->getName() => $this->parseLiteralForEdmType(
                trim($keyString),
                $keyProp->getType()->getQualifiedName()
            ),
        ]);
    }

    /** @param list<PropertyInterface> $keyProperties */
    private function findKeyProperty(string $name, array $keyProperties): PropertyInterface
    {
        foreach ($keyProperties as $kp) {
            if ($kp->getName() === $name) {
                return $kp;
            }
        }
        throw new BadRequestException('unknown_key_property', "Unknown key property: {$name}");
    }

    private function parseLiteralForEdmType(string $raw, string $edmType): LiteralExpression
    {
        return match (true) {
            in_array($edmType, ['Edm.Int16', 'Edm.Int32', 'Edm.Int64', 'Edm.Byte', 'Edm.SByte'], true)
                => new LiteralExpression((int) $raw, $edmType),
            in_array($edmType, ['Edm.Double', 'Edm.Decimal', 'Edm.Single'], true)
                => new LiteralExpression((float) $raw, $edmType),
            $edmType === 'Edm.Boolean'
                => new LiteralExpression(strtolower($raw) === 'true', $edmType),
            $edmType === 'Edm.String'
                => new LiteralExpression(trim($raw, "'"), $edmType),
            default
                => new LiteralExpression($raw, $edmType),
        };
    }

    // -------------------------------------------------------------------------
    // $select
    // -------------------------------------------------------------------------

    private function parseSelectList(
        ?string              $selectString,
        ?EntitySetInterface  $entitySet = null,
        ?EntityTypeInterface $entityType = null,
    ): SelectList {
        if ($selectString === null) {
            return new SelectList();
        }

        if ($selectString === '*') {
            return new SelectList([new WildcardSelectItem()]);
        }

        $entityType = $entityType ?? $entitySet->getEntityType();
        $items      = [];

        foreach (array_filter(array_map('trim', explode(',', $selectString))) as $name) {
            if ($name === '*') {
                $items[] = new WildcardSelectItem();
                continue;
            }

            $property = $entityType->getProperty($name);
            if ($property === null) {
                throw new BadRequestException(
                    'unknown_property',
                    "Unknown property in \$select: {$name}"
                );
            }

            $items[] = new PropertySelectItem($property);
        }

        return new SelectList($items);
    }

    // -------------------------------------------------------------------------
    // $orderby
    // -------------------------------------------------------------------------

    private function parseOrderByList(?string $orderByString, EntityTypeInterface $entityType): OrderByList
    {
        if ($orderByString === null) {
            return new OrderByList();
        }

        $items = [];

        foreach (array_filter(array_map('trim', explode(',', $orderByString))) as $clause) {
            $parts     = array_values(array_filter(array_map('trim', explode(' ', $clause))));
            $propName  = $parts[0] ?? '';
            $direction = strtolower($parts[1] ?? 'asc');

            if (!in_array($direction, ['asc', 'desc'], true)) {
                throw new BadRequestException('invalid_orderby_direction', "Invalid \$orderby direction: {$direction}");
            }

            $property = $entityType->getProperty($propName);
            if ($property === null) {
                throw new BadRequestException(
                    'unknown_property',
                    "Unknown property in \$orderby: {$propName}"
                );
            }

            $items[] = new OrderByItem(
                expression: new PropertyPathExpression([$property]),
                direction:  $direction === 'desc' ? OrderDirection::Desc : OrderDirection::Asc,
            );
        }

        return new OrderByList($items);
    }

    // -------------------------------------------------------------------------
    // $expand
    // -------------------------------------------------------------------------

    private function parseExpandList(
        ?string                $expandString,
        EntitySetInterface     $entitySet,
        RuntimeSchemaInterface $schema,
    ): ExpandList {
        if ($expandString === null || $expandString === '') {
            return new ExpandList();
        }

        $entityType = $entitySet->getEntityType();
        $container  = $schema->getEdmx()->getEntityContainer();
        $items      = [];

        // Split on commas that are NOT inside parentheses.
        foreach ($this->splitExpandClauses($expandString) as $clause) {
            // Parse optional nested options: "navName($select=a;$top=5)"
            if (preg_match('/^([^(]+)\((.+)\)$/', $clause, $em)) {
                $navName      = trim($em[1]);
                $nestedString = $em[2];
            } else {
                $navName      = trim($clause);
                $nestedString = null;
            }

            $navProp = $entityType->getNavigationProperty($navName);
            if ($navProp === null) {
                throw new BadRequestException(
                    'unknown_navigation_property',
                    sprintf('Unknown navigation property "%s" on entity type "%s"', $navName, $entityType->getName())
                );
            }

            $binding = $entitySet->getNavigationPropertyBinding($navName);
            if ($binding === null) {
                throw new BadRequestException(
                    'unbound_navigation_property',
                    sprintf('No navigation property binding for "%s" on entity set "%s"', $navName, $entitySet->getName())
                );
            }

            $targetSet = $container->getEntitySet($binding->getTarget());
            if ($targetSet === null) {
                throw new BadRequestException(
                    'unknown_target_set',
                    sprintf('Target entity set "%s" not found for navigation "%s"', $binding->getTarget(), $navName)
                );
            }

            // Parse nested options if present.
            $nestedOpts = $this->parseNestedExpandOptions($nestedString, $targetSet, $schema);

            $items[] = new ExpandItem(
                property:  $navProp,
                targetSet: $targetSet,
                filter:    $nestedOpts['filter'],
                select:    $nestedOpts['select'],
                expand:    $nestedOpts['expand'],
                orderBy:   $nestedOpts['orderBy'],
                top:       $nestedOpts['top'],
                skip:      $nestedOpts['skip'],
                count:     $nestedOpts['count'],
            );
        }

        return new ExpandList($items);
    }

    /**
     * Split top-level expand clauses on commas, respecting parentheses nesting.
     *
     * @return list<string>
     */
    private function splitExpandClauses(string $expandString): array
    {
        $clauses = [];
        $current = '';
        $depth   = 0;

        for ($i = 0, $len = strlen($expandString); $i < $len; $i++) {
            $ch = $expandString[$i];
            if ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth--;
            } elseif ($ch === ',' && $depth === 0) {
                $clauses[] = trim($current);
                $current   = '';
                continue;
            }
            $current .= $ch;
        }

        if (trim($current) !== '') {
            $clauses[] = trim($current);
        }

        return $clauses;
    }

    /**
     * Parse semicolon-separated nested options inside $expand parentheses.
     *
     * @return array{filter: ?FilterExpression, select: SelectList, orderBy: ?OrderByList, top: ?int, skip: ?int, count: bool}
     */
    private function parseNestedExpandOptions(
        ?string                $nestedString,
        EntitySetInterface     $targetSet,
        RuntimeSchemaInterface $schema,
    ): array {
        $result = [
            'filter'  => null,
            'select'  => new SelectList(),
            'expand'  => new ExpandList(),
            'orderBy' => null,
            'top'     => null,
            'skip'    => null,
            'count'   => false,
        ];

        if ($nestedString === null || $nestedString === '') {
            return $result;
        }

        $targetType = $targetSet->getEntityType();

        // Split on semicolons: $select=name;$top=5;$filter=...
        foreach (explode(';', $nestedString) as $option) {
            $option = trim($option);
            if ($option === '') {
                continue;
            }

            $eqPos = strpos($option, '=');
            if ($eqPos === false) {
                continue;
            }

            $key   = trim(substr($option, 0, $eqPos));
            $value = trim(substr($option, $eqPos + 1));

            match ($key) {
                '$select'  => $result['select']  = $this->parseSelectList($value, $targetSet),
                '$filter'  => $result['filter']  = $this->parseFilterExpression($value, $targetType),
                '$expand'  => $result['expand']  = $this->parseExpandList($value, $targetSet, $schema),
                '$orderby' => $result['orderBy'] = $this->parseOrderByList($value, $targetType),
                '$top'     => $result['top']     = (int) $value,
                '$skip'    => $result['skip']    = (int) $value,
                '$count'   => $result['count']   = $value === 'true',
                default    => null, // ignore unknown options
            };
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // $filter — direct FilterExpression parsing
    // -------------------------------------------------------------------------

    private function parseFilterExpression(string $filterString, EntityTypeInterface $entityType): FilterExpression
    {
        $parser   = new \LaravelUi5\OData\Protocol\Parser\FilterParser();
        $resolver = new \LaravelUi5\OData\Protocol\Parser\PropertyResolver();

        $unresolved = $parser->parse($filterString);
        return $resolver->resolve($unresolved, $entityType);
    }

    // -------------------------------------------------------------------------
    // Function import parameters
    // -------------------------------------------------------------------------

    /**
     * Parse function import parameters from the URL parentheses.
     *
     * Supports: FuncName(param='value',num=42) and FuncName() (no params).
     *
     * @return array<string, LiteralExpression>
     */
    private function parseFunctionParameters(FunctionImportInterface $import, ?string $paramString): array
    {
        $function = $import->getFunction();
        $declared = $function->getParameters();

        if ($paramString === null || $paramString === '') {
            return [];
        }

        $pairs  = explode(',', $paramString);
        $result = [];

        foreach ($pairs as $pair) {
            $eqPos = strpos($pair, '=');
            if ($eqPos === false) {
                throw new BadRequestException(
                    'invalid_function_parameter',
                    sprintf('Invalid function parameter syntax: "%s"', $pair)
                );
            }

            $name  = trim(substr($pair, 0, $eqPos));
            $raw   = trim(substr($pair, $eqPos + 1));

            $param = $function->getParameter($name);
            if ($param === null) {
                throw new BadRequestException(
                    'unknown_function_parameter',
                    sprintf('Unknown parameter "%s" for function "%s"', $name, $function->getName())
                );
            }

            $result[$name] = $this->parseLiteralValue($raw);
        }

        return $result;
    }

    /**
     * Parse a raw literal value from a URL into a LiteralExpression.
     */
    private function parseLiteralValue(string $raw): LiteralExpression
    {
        // String literal: 'value'
        if (str_starts_with($raw, "'") && str_ends_with($raw, "'")) {
            return new LiteralExpression(substr($raw, 1, -1), 'Edm.String');
        }

        // Boolean
        if ($raw === 'true' || $raw === 'false') {
            return new LiteralExpression($raw === 'true', 'Edm.Boolean');
        }

        // Null
        if ($raw === 'null') {
            return new LiteralExpression(null, 'Edm.Null');
        }

        // Integer
        if (preg_match('/^-?\d+$/', $raw)) {
            return new LiteralExpression((int) $raw, 'Edm.Int32');
        }

        // Decimal / float
        if (is_numeric($raw)) {
            return new LiteralExpression((float) $raw, 'Edm.Decimal');
        }

        // Default: treat as unquoted string
        return new LiteralExpression($raw, 'Edm.String');
    }

    // -------------------------------------------------------------------------
    // $compute
    // -------------------------------------------------------------------------

    /**
     * Parse $compute string into ComputedProperty definitions.
     *
     * Format: "expression as alias[,expression as alias,...]"
     *
     * @return list<ComputedProperty>
     */
    private function parseCompute(?string $computeString): array
    {
        if ($computeString === null || $computeString === '') {
            return [];
        }

        $result = [];

        // Split on commas that are NOT inside parentheses.
        foreach ($this->splitExpandClauses($computeString) as $clause) {
            $clause = trim($clause);
            // Match "expression as alias" — last " as " separator.
            $asPos = strrpos($clause, ' as ');
            if ($asPos === false) {
                throw new BadRequestException(
                    'invalid_compute',
                    sprintf('Invalid $compute clause: "%s" (missing "as" alias)', $clause)
                );
            }

            $expression = trim(substr($clause, 0, $asPos));
            $alias      = trim(substr($clause, $asPos + 4));

            if ($expression === '' || $alias === '') {
                throw new BadRequestException(
                    'invalid_compute',
                    sprintf('Invalid $compute clause: "%s"', $clause)
                );
            }

            $result[] = new ComputedProperty($alias, $expression);
        }

        return $result;
    }
}
