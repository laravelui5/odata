<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\AnnotationValueWriter;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Contracts\Annotation\CollectionAnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\ConstantAnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\RecordAnnotationValueInterface;

use function LaravelUi5\OData\Tests\narrow;

/*
 * Records of vocabulary complex types and the value writer behind them (OP07, step 2). The classes below have the
 * shape the generator writes.
 */

enum RecordTestCriticality: int
{
    case Neutral = 0;
    case Positive = 3;
}

enum RecordTestFlag: int
{
    case Read = 1;
    case Write = 2;
}

abstract readonly class RecordTestFieldAbstract extends AbstractRecord
{
    public const string TYPE = 'test.Ui.v1.FieldAbstract';

    public const array FIELDS = [
        'Label'       => ['label', 'Edm.String', false],
        'Criticality' => ['criticality', 'test.Ui.v1.Criticality', false],
    ];

    public function __construct(
        public string|Path|null $label = null,
        public RecordTestCriticality|Path|null $criticality = null,
    ) {}
}

final readonly class RecordTestField extends RecordTestFieldAbstract
{
    public const string TYPE = 'test.Ui.v1.Field';

    public const array FIELDS = [
        'Label'       => ['label', 'Edm.String', false],
        'Criticality' => ['criticality', 'test.Ui.v1.Criticality', false],
        'Value'       => ['value', 'Edm.Untyped', true],
        'Hidden'      => ['hidden', 'Edm.Boolean', false],
        'Width'       => ['width', 'Edm.Int32', false],
        'Target'      => ['target', 'Edm.NavigationPropertyPath', false],
        'Access'      => ['access', 'test.Ui.v1.Flag', false],
        'Nested'      => ['nested', 'Collection(test.Ui.v1.FieldAbstract)', false],
    ];

    /**
     * @param RecordTestFlag|list<RecordTestFlag>|Path|null $access
     * @param list<RecordTestFieldAbstract>|Path|null $nested
     */
    public function __construct(
        string|Path|null $label = null,
        RecordTestCriticality|Path|null $criticality = null,
        public mixed $value = null,
        public bool|Path|null $hidden = null,
        public int|Path|null $width = null,
        public ?string $target = null,
        public RecordTestFlag|array|Path|null $access = null,
        public array|Path|null $nested = null,
    ) {
        parent::__construct(
            label: $label,
            criticality: $criticality,
        );
    }
}

/** @return array<string, string> field → "Kind=value" */
function recordFields(RecordAnnotationValueInterface $record): array
{
    $fields = [];
    foreach ($record->getPropertyValues() as $pv) {
        $value = $pv->getValue();
        $fields[$pv->getProperty()] = $value instanceof ConstantAnnotationValueInterface
            ? "{$value->getKind()}={$value->getValue()}"
            : get_debug_type($value);
    }

    return $fields;
}

it('writes a record with its qualified type and only the fields that are set', function () {
    $record = (new RecordTestField(value: new Path('code'), label: 'Code'))->toRecord();

    expect($record->getType())->toBe('test.Ui.v1.Field')
        ->and(recordFields($record))->toBe(['Label' => 'String=Code', 'Value' => 'Path=code']);
});

it('writes each field in the form its type asks for', function () {
    $record = (new RecordTestField(
        criticality: RecordTestCriticality::Positive,
        value: 42,
        hidden: false,
        width: 12,
        target: 'parent',
        access: [RecordTestFlag::Read, RecordTestFlag::Write],
    ))->toRecord();

    expect(recordFields($record))->toBe([
        'Criticality' => 'EnumMember=test.Ui.v1.Criticality/Positive',
        'Value'       => 'Int=42',
        'Hidden'      => 'Bool=false',
        'Width'       => 'Int=12',
        'Target'      => 'NavigationPropertyPath=parent',
        'Access'      => 'EnumMember=test.Ui.v1.Flag/Read test.Ui.v1.Flag/Write',
    ]);
});

it('takes a Path in a field of any type', function () {
    expect(recordFields((new RecordTestField(value: 'x', criticality: new Path('crit'), hidden: new Path('isHidden')))->toRecord()))
        ->toMatchArray(['Criticality' => 'Path=crit', 'Hidden' => 'Path=isHidden']);
});

it('nests records in a collection field', function () {
    $nested = narrow(
        CollectionAnnotationValueInterface::class,
        (new RecordTestField(value: 'x', nested: [new RecordTestField(value: true)]))->toRecord()
            ->getPropertyValue('Nested')?->getValue(),
    );
    $item = narrow(RecordAnnotationValueInterface::class, $nested->getItems()[0] ?? null);

    expect($nested->getItems())->toHaveCount(1)
        ->and($item->getType())->toBe('test.Ui.v1.Field')
        ->and(recordFields($item))->toBe(['Value' => 'Bool=true']);
});

it('fails loud when a required field is not set', function () {
    (new RecordTestField(label: 'Code'))->toRecord();
})->throws(LogicException::class, 'The record test.Ui.v1.Field requires the field Value (named argument $value).');

it('refuses a value it cannot write', function () {
    AnnotationValueWriter::write(new stdClass(), 'Edm.Untyped');
})->throws(LogicException::class, 'A value of type stdClass cannot be written for Edm.Untyped.');

it('refuses a scalar where a vocabulary type asks for a record or a member', function () {
    AnnotationValueWriter::write(['code'], 'Collection(test.Ui.v1.FieldAbstract)');
})->throws(LogicException::class, 'A test.Ui.v1.FieldAbstract takes a record or an enumeration member, string given.');

it('refuses a scalar for a collection', function () {
    AnnotationValueWriter::write('x', 'Collection(Edm.String)');
})->throws(LogicException::class, 'A Collection(Edm.String) takes a list, string given.');
