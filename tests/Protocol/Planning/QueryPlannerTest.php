<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Exception\BadRequestException;
use LaravelUi5\OData\Http\ODataRequest;
use LaravelUi5\OData\Protocol\Planning\BatchQueryPlan;
use LaravelUi5\OData\Protocol\Planning\EntityQueryPlan;
use LaravelUi5\OData\Protocol\Planning\EntitySetQueryPlan;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;
use LaravelUi5\OData\Protocol\Planning\MetadataQueryPlan;
use LaravelUi5\OData\Protocol\Planning\OrderDirection;
use LaravelUi5\OData\Protocol\Planning\PropertySelectItem;
use LaravelUi5\OData\Protocol\Planning\QueryPlanner;
use LaravelUi5\OData\Protocol\Planning\ServiceDocumentQueryPlan;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Contracts\EntitySetResolverInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;

// ── Helpers ────────────────────────────────────────────────────────────────────

/**
 * Build a minimal EdmxInterface with a single entity set "Products" whose
 * entity type "Product" has:
 *   - key property "id" (Edm.Int32)
 *   - property  "name" (Edm.String)
 *   - property  "code" (Edm.String)
 */
function plannerEdmx(): EdmxInterface
{
    $int32  = new PrimitiveType(EdmPrimitiveType::Int32);
    $string = new PrimitiveType(EdmPrimitiveType::String);

    $idProp   = new Property('id',   $int32);
    $nameProp = new Property('name', $string);
    $codeProp = new Property('code', $string);

    $productType = new EntityType(
        namespace: 'Test.Ns',
        name: 'Product',
        key: [$idProp],
        declaredProperties: [$idProp, $nameProp, $codeProp],
    );

    $productSet = new EntitySet('Products', $productType);

    return (new EdmBuilder)
        ->namespace('Test.Ns')
        ->addEntityType($productType)
        ->addEntitySet($productSet)
        ->build();
}

function plannerSchema(?EdmxInterface $edmx = null): RuntimeSchemaInterface
{
    $e = $edmx ?? plannerEdmx();

    return new class ($e) implements RuntimeSchemaInterface {
        public function __construct(private EdmxInterface $edmx) {}
        public function getEdmx(): EdmxInterface { return $this->edmx; }
        public function getResolver(EntitySetInterface $set): EntitySetResolverInterface
        {
            throw new \LogicException('getResolver() must not be called during planning');
        }
        public function getFunctionResolver(\LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface $import): \LaravelUi5\OData\Service\Contracts\FunctionResolverInterface
        {
            throw new \LogicException('getFunctionResolver() must not be called during planning');
        }
        public function getSingletonResolver(\LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface $singleton): \LaravelUi5\OData\Service\Contracts\SingletonResolverInterface
        {
            throw new \LogicException('getSingletonResolver() must not be called during planning');
        }
    };
}

// ── Tests ──────────────────────────────────────────────────────────────────────

describe('QueryPlanner', function () {

    // ---- service-document / metadata / batch --------------------------------

    it('returns ServiceDocumentQueryPlan for empty path', function () {
        $plan = (new QueryPlanner)->plan(new ODataRequest(''), plannerSchema());
        expect($plan)->toBeInstanceOf(ServiceDocumentQueryPlan::class);
        expect($plan->edmx)->toBeInstanceOf(EdmxInterface::class);
    });

    it('returns ServiceDocumentQueryPlan for root slash', function () {
        $plan = (new QueryPlanner)->plan(new ODataRequest('/'), plannerSchema());
        expect($plan)->toBeInstanceOf(ServiceDocumentQueryPlan::class);
    });

    it('returns MetadataQueryPlan for $metadata', function () {
        $plan = (new QueryPlanner)->plan(new ODataRequest('/$metadata'), plannerSchema());
        expect($plan)->toBeInstanceOf(MetadataQueryPlan::class);
        expect($plan->edmx)->toBeInstanceOf(EdmxInterface::class);
    });

    it('carries the same EdmxInterface instance in MetadataQueryPlan', function () {
        $schema = plannerSchema();
        $plan   = (new QueryPlanner)->plan(new ODataRequest('/$metadata'), $schema);
        expect($plan->edmx)->toBe($schema->getEdmx());
    });

    it('returns BatchQueryPlan for $batch', function () {
        $plan = (new QueryPlanner)->plan(new ODataRequest('/$batch'), plannerSchema());
        expect($plan)->toBeInstanceOf(BatchQueryPlan::class);
    });

    // ---- entity set collection -----------------------------------------------

    it('returns EntitySetQueryPlan for a known entity set', function () {
        $plan = (new QueryPlanner)->plan(new ODataRequest('/Products'), plannerSchema());
        expect($plan)->toBeInstanceOf(EntitySetQueryPlan::class);
    });

    it('resolves the correct EntitySetInterface on EntitySetQueryPlan', function () {
        $schema = plannerSchema();
        $plan   = (new QueryPlanner)->plan(new ODataRequest('/Products'), $schema);
        expect($plan->target->getName())->toBe('Products');
    });

    it('defaults to null filter on EntitySetQueryPlan', function () {
        $plan = (new QueryPlanner)->plan(new ODataRequest('/Products'), plannerSchema());
        expect($plan->filter)->toBeNull();
    });

    it('defaults to empty select (select-all) on EntitySetQueryPlan', function () {
        $plan = (new QueryPlanner)->plan(new ODataRequest('/Products'), plannerSchema());
        expect($plan->select->isSelectAll())->toBeTrue();
    });

    it('defaults to empty orderby on EntitySetQueryPlan', function () {
        $plan = (new QueryPlanner)->plan(new ODataRequest('/Products'), plannerSchema());
        expect($plan->orderBy->isEmpty())->toBeTrue();
    });

    it('defaults top and skip to null', function () {
        $plan = (new QueryPlanner)->plan(new ODataRequest('/Products'), plannerSchema());
        expect($plan->top)->toBeNull();
        expect($plan->skip)->toBeNull();
    });

    // ---- single entity by key ------------------------------------------------

    it('returns EntityQueryPlan for entity set with positional key', function () {
        $plan = (new QueryPlanner)->plan(new ODataRequest('/Products(1)'), plannerSchema());
        expect($plan)->toBeInstanceOf(EntityQueryPlan::class);
    });

    it('builds correct KeyExpression for single positional key', function () {
        $plan = (new QueryPlanner)->plan(new ODataRequest('/Products(42)'), plannerSchema());
        expect($plan->key->isSingleKey())->toBeTrue();
        expect($plan->key->values['id']->value)->toBe(42);
        expect($plan->key->values['id']->edmType)->toBe('Edm.Int32');
    });

    it('builds correct KeyExpression for named key', function () {
        $plan = (new QueryPlanner)->plan(new ODataRequest('/Products(id=7)'), plannerSchema());
        expect($plan->key->values['id']->value)->toBe(7);
        expect($plan->key->values['id']->edmType)->toBe('Edm.Int32');
    });

    // ---- $filter -------------------------------------------------------------

    it('builds BinaryExpression for integer equality filter', function () {
        $plan = (new QueryPlanner)->plan(
            new ODataRequest('/Products', filter: 'id eq 42'),
            plannerSchema()
        );

        expect($plan->filter)->toBeInstanceOf(BinaryExpression::class);
        expect($plan->filter->operator)->toBe(BinaryOperator::Eq);
        expect($plan->filter->left)->toBeInstanceOf(PropertyPathExpression::class);
        expect($plan->filter->left->segments[0]->getName())->toBe('id');
        expect($plan->filter->right)->toBeInstanceOf(LiteralExpression::class);
        expect($plan->filter->right->value)->toBe(42);
        expect($plan->filter->right->edmType)->toBe('Edm.Int32');
    });

    it('infers literal Edm type from the compared property', function () {
        $plan = (new QueryPlanner)->plan(
            new ODataRequest('/Products', filter: "name eq 'Widget'"),
            plannerSchema()
        );

        expect($plan->filter->right->edmType)->toBe('Edm.String');
        expect($plan->filter->right->value)->toBe('Widget');
    });

    // ---- $select -------------------------------------------------------------

    it('parses a two-property $select into SelectList', function () {
        $plan = (new QueryPlanner)->plan(
            new ODataRequest('/Products', select: 'name,code'),
            plannerSchema()
        );

        expect($plan->select->isSelectAll())->toBeFalse();
        expect($plan->select->items)->toHaveCount(2);
        expect($plan->select->items[0])->toBeInstanceOf(PropertySelectItem::class);
        expect($plan->select->items[0]->property->getName())->toBe('name');
        expect($plan->select->items[1]->property->getName())->toBe('code');
    });

    // ---- $orderby ------------------------------------------------------------

    it('parses $orderby asc into OrderByList', function () {
        $plan = (new QueryPlanner)->plan(
            new ODataRequest('/Products', orderBy: 'name asc'),
            plannerSchema()
        );

        expect($plan->orderBy->isEmpty())->toBeFalse();
        expect($plan->orderBy->items[0]->direction)->toBe(OrderDirection::Asc);
        expect($plan->orderBy->items[0]->expression)->toBeInstanceOf(PropertyPathExpression::class);
        expect($plan->orderBy->items[0]->expression->segments[0]->getName())->toBe('name');
    });

    it('defaults to Asc when direction is omitted from $orderby', function () {
        $plan = (new QueryPlanner)->plan(
            new ODataRequest('/Products', orderBy: 'name'),
            plannerSchema()
        );

        expect($plan->orderBy->items[0]->direction)->toBe(OrderDirection::Asc);
    });

    it('parses $orderby desc', function () {
        $plan = (new QueryPlanner)->plan(
            new ODataRequest('/Products', orderBy: 'name desc'),
            plannerSchema()
        );

        expect($plan->orderBy->items[0]->direction)->toBe(OrderDirection::Desc);
    });

    // ---- $top / $skip --------------------------------------------------------

    it('carries top and skip from the request', function () {
        $plan = (new QueryPlanner)->plan(
            new ODataRequest('/Products', top: 10, skip: 5),
            plannerSchema()
        );

        expect($plan->top)->toBe(10);
        expect($plan->skip)->toBe(5);
    });

    // ---- error cases ---------------------------------------------------------

    it('throws BadRequestException for an unknown entity set', function () {
        expect(fn() =>
            (new QueryPlanner)->plan(new ODataRequest('/Nonexistent'), plannerSchema())
        )->toThrow(BadRequestException::class);
    });

    it('throws BadRequestException for an unknown property in $filter', function () {
        expect(fn() =>
            (new QueryPlanner)->plan(
                new ODataRequest('/Products', filter: 'unknownProp eq 1'),
                plannerSchema()
            )
        )->toThrow(BadRequestException::class);
    });

    it('throws BadRequestException for an unknown property in $select', function () {
        expect(fn() =>
            (new QueryPlanner)->plan(
                new ODataRequest('/Products', select: 'nonexistent'),
                plannerSchema()
            )
        )->toThrow(BadRequestException::class);
    });

    it('throws BadRequestException for an unknown property in $orderby', function () {
        expect(fn() =>
            (new QueryPlanner)->plan(
                new ODataRequest('/Products', orderBy: 'nonexistent asc'),
                plannerSchema()
            )
        )->toThrow(BadRequestException::class);
    });
});
