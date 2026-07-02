<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use LaravelUi5\OData\Driver\Sql\SqlEntitySetResolver;
use LaravelUi5\OData\Edm\Container\EnumType;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Service\Contracts\CustomEntitySetInterface;
use LaravelUi5\OData\Service\Contracts\SqlQueryInterface;

/**
 * Declarative base class for SQL-backed custom entity sets.
 *
 * Subclasses declare their schema via {@see columns()} and {@see key()},
 * and provide the SQL source via {@see query()}. The entity type is
 * assembled automatically — no manual EDM construction needed.
 *
 * Example:
 *
 *     final readonly class BillableProjects extends AbstractEntitySet
 *     {
 *         public function entitySetName(): string { return 'BillableProjects'; }
 *
 *         public function key(): array { return ['project_id']; }
 *
 *         public function columns(): array
 *         {
 *             return [
 *                 'project_id'   => EdmPrimitiveType::Int64,
 *                 'customer'     => EdmPrimitiveType::String,
 *                 'tier'         => LicenseTier::class,         // int-backed PHP enum
 *                 'hours_posted' => EdmPrimitiveType::Double,
 *             ];
 *         }
 *
 *         public function query(CustomQueryOptions $options): Builder
 *         {
 *             return DB::query()->fromSub($sql, 't');
 *         }
 *     }
 *
 * The entity type name is derived by singularizing {@see entitySetName()}
 * (e.g. BillableProjects → BillableProject). Override {@see entityType()}
 * for full control over entity type construction (custom names, navigation
 * properties, annotations, non-standard key configurations).
 */
abstract readonly class AbstractEntitySet extends SqlEntitySetResolver implements CustomEntitySetInterface, SqlQueryInterface
{
    public function __construct()
    {
        parent::__construct($this);
    }

    /**
     * Flat column definitions.
     *
     * Each value is either an {@see EdmPrimitiveType} case or the class-string
     * of an int-backed PHP enum (auto-projected to an `Edm.EnumType`).
     *
     * @return array<string, EdmPrimitiveType|class-string<\BackedEnum>>
     */
    abstract public function columns(): array;

    /**
     * Primary key column name(s). Defaults to the first column in columns().
     *
     * Override for composite keys: ['tenant_id', 'project_id'].
     *
     * @return list<string>
     */
    public function key(): array
    {
        return [array_key_first($this->columns())];
    }

    /**
     * Builds the entity type from columns() + key() + entitySetName().
     *
     * The entity type name is the singular form of entitySetName().
     * Override this method for full control (nav props, annotations, etc.).
     */
    public function entityType(string $namespace): EntityTypeInterface
    {
        $properties = [];

        foreach ($this->columns() as $name => $type) {
            $resolved          = $type instanceof EdmPrimitiveType
                ? new PrimitiveType($type)
                : EnumType::fromBackedEnum($namespace, $type);
            $properties[$name] = new Property($name, $resolved);
        }

        $keyProps = array_values(
            array_intersect_key($properties, array_flip($this->key())),
        );

        return new EntityType(
            namespace: $namespace,
            name: Str::singular($this->entitySetName()),
            key: $keyProps,
            declaredProperties: array_values($properties),
        );
    }
}
