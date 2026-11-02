<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\ODataServiceProvider;
use LaravelUi5\OData\Service\Serialization\CsdlSerializer;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Cache\EdmxWriter;
use LaravelUi5\OData\Service\Contracts\ColumnAnnotationResolverInterface;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataAnnotate;
use LaravelUi5\OData\Service\Discovery\ColumnsUnannotated;
use LaravelUi5\OData\Service\Discovery\ModelDiscovery;
use LaravelUi5\OData\Tests\TestCase;
use LaravelUi5\OData\Vocabularies\Common\V1\Label;
use LaravelUi5\OData\Vocabularies\Core\V1\Description;
use LaravelUi5\OData\Vocabularies\Core\V1\IsLanguageDependent;

/*
 * OP35 + OP38 (2026-11-02): a discovered column is annotated on the model's class with #[ODataAnnotate], or across
 * models by a bound ColumnAnnotationResolverInterface. On the same term and qualifier the attribute wins.
 */

uses(TestCase::class);

#[ODataAnnotate('name', new Label('Product name'), new Description('Shown in lists'))]
#[ODataAnnotate('name', new Label('Short', qualifier: 'Tile'))]
#[ODataAnnotate('code', new Label('Code'))]
class AnnotatedProduct extends Model
{
    protected $table = 'annotated_products';
    public $timestamps = false;
}

/** Names a column the table does not have. */
#[ODataAnnotate('nmae', new Label('Typo'))]
class MistypedProduct extends Model
{
    protected $table = 'annotated_products';
    public $timestamps = false;
}

/** Names a column the model never serializes. */
#[ODataAnnotate('secret', new Label('Secret'))]
class HiddenAnnotatedProduct extends Model
{
    protected $table = 'annotated_products';
    public $timestamps = false;
    protected $hidden = ['secret'];
}

/** Stands in for the SDK: translated fields are language-dependent, and it has a label of its own for `name`. */
final class TranslatedColumns implements ColumnAnnotationResolverInterface
{
    /** @var list<array{string, string}> */
    public array $calls = [];

    public function annotations(string $modelClass, string $column): array
    {
        $this->calls[] = [$modelClass, $column];

        return $column === 'name' ? [new IsLanguageDependent(), new Label('From the resolver')] : [];
    }
}

/** @param list<class-string<Model>> $models */
function annotatedEdmx(array $models, ?ColumnAnnotationResolverInterface $resolver = null): EdmxInterface
{
    $discovery = new ModelDiscovery(annotationResolver: $resolver);
    foreach ($models as $model) {
        $discovery->add($model);
    }

    $builder = (new EdmBuilder())->namespace('Test.Ns');
    $discovery->apply($builder, 'Test.Ns');

    return $builder->build();
}

function annotatedType(EdmxInterface $edmx): EntityTypeInterface
{
    return $edmx->getSchema('Test.Ns')->getEntityTypes()[0];
}

/** @return array<string, string|null> term#qualifier => the annotation's constant value, if any */
function columnTerms(EntityTypeInterface $type, string $column): array
{
    $terms = [];
    foreach ($type->getProperty($column)->getAnnotations() as $annotation) {
        $key         = $annotation->getTerm() . ($annotation->getQualifier() !== null ? '#' . $annotation->getQualifier() : '');
        $terms[$key] = $annotation->getValue()?->getValue();
    }

    return $terms;
}

function annotatedWriteAndLoad(EdmxInterface $edmx, string $dir, string $namespace): EdmxInterface
{
    (new EdmxWriter($edmx, $dir . '/Edm', $namespace))->write();

    spl_autoload_register(function (string $class) use ($namespace, $dir) {
        if (str_starts_with($class, $namespace . '\\')) {
            $file = $dir . '/Edm/' . str_replace('\\', '/', substr($class, strlen($namespace) + 1)) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    });

    require_once $dir . '/Edm/Edmx.php';
    $edmxClass = $namespace . '\\Edmx';

    return new $edmxClass();
}

beforeEach(function () {
    DB::statement('CREATE TABLE annotated_products (id integer primary key, code varchar(10), name varchar(40), secret varchar(40))');
});

describe('#[ODataAnnotate]', function () {
    it('puts its annotations on the property it names', function () {
        expect(columnTerms(annotatedType(annotatedEdmx([AnnotatedProduct::class])), 'name'))->toBe([
            'com.sap.vocabularies.Common.v1.Label'      => 'Product name',
            'Org.OData.Core.V1.Description'             => 'Shown in lists',
            'com.sap.vocabularies.Common.v1.Label#Tile' => 'Short',
        ]);
    });

    it('leaves the other columns to their own attribute', function () {
        $type = annotatedType(annotatedEdmx([AnnotatedProduct::class]));

        expect(columnTerms($type, 'code'))->toBe(['com.sap.vocabularies.Common.v1.Label' => 'Code'])
            ->and(columnTerms($type, 'secret'))->toBe([]);
    });

    it('writes them into $metadata', function () {
        expect((new CsdlSerializer())->serialize(annotatedEdmx([AnnotatedProduct::class])))
            ->toContain('<Annotation Term="com.sap.vocabularies.Common.v1.Label" String="Product name"/>')
            ->toContain('<Annotation Term="com.sap.vocabularies.Common.v1.Label" Qualifier="Tile" String="Short"/>');
    });

    it('fails loud on a column the table does not have', function () {
        annotatedEdmx([MistypedProduct::class]);
    })->throws(LogicException::class, '#[ODataAnnotate] on MistypedProduct names "nmae"');

    it('fails loud on a column the model never serializes', function () {
        annotatedEdmx([HiddenAnnotatedProduct::class]);
    })->throws(LogicException::class, '#[ODataAnnotate] on HiddenAnnotatedProduct names "secret"');
});

describe('ColumnAnnotationResolverInterface', function () {
    it('binds the empty resolver by default', function () {
        expect(app(ColumnAnnotationResolverInterface::class))->toBeInstanceOf(ColumnsUnannotated::class);
    });

    it('keeps a resolver bound before the provider registers', function () {
        $this->app->bind(ColumnAnnotationResolverInterface::class, TranslatedColumns::class);

        (new ODataServiceProvider($this->app))->register();

        expect(app(ColumnAnnotationResolverInterface::class))->toBeInstanceOf(TranslatedColumns::class);
    });

    it('is asked per declared column, and resolved through the container when none is passed', function () {
        $resolver = new TranslatedColumns();
        $this->app->instance(ColumnAnnotationResolverInterface::class, $resolver);

        $type = annotatedType(annotatedEdmx([AnnotatedProduct::class]));

        expect($resolver->calls)->toBe([
            [AnnotatedProduct::class, 'id'],
            [AnnotatedProduct::class, 'code'],
            [AnnotatedProduct::class, 'name'],
            [AnnotatedProduct::class, 'secret'],
        ])->and(columnTerms($type, 'name'))->toHaveKey('Org.OData.Core.V1.IsLanguageDependent');
    });

    it('lets the attribute win on the same term and qualifier, and keeps the rest', function () {
        $terms = columnTerms(annotatedType(annotatedEdmx([AnnotatedProduct::class], new TranslatedColumns())), 'name');

        expect($terms)->toBe([
            'Org.OData.Core.V1.IsLanguageDependent'     => null,
            'com.sap.vocabularies.Common.v1.Label'      => 'Product name',
            'Org.OData.Core.V1.Description'             => 'Shown in lists',
            'com.sap.vocabularies.Common.v1.Label#Tile' => 'Short',
        ]);
    });

    it('survives odata:cache: the warm $metadata is the cold one', function () {
        $cold = annotatedEdmx([AnnotatedProduct::class], new TranslatedColumns());
        $dir  = sys_get_temp_dir() . '/column_annotations_test_' . getmypid();
        $ns   = 'ColumnAnnotationsTest\\Edm';

        $warm = annotatedWriteAndLoad($cold, $dir, $ns);

        $serializer = new CsdlSerializer();
        expect($serializer->serialize($warm))->toBe($serializer->serialize($cold))
            ->toContain('<Annotation Term="Org.OData.Core.V1.IsLanguageDependent"/>');

        (new Illuminate\Filesystem\Filesystem())->deleteDirectory($dir);
    });
});
