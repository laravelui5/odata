<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Contracts\Annotation\RecordAnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Vocabularies\VocabularyCatalog;

use function LaravelUi5\OData\Tests\Vocabularies\annotatedCsdl;
use function LaravelUi5\OData\Tests\Vocabularies\csdlSchemaErrors;
use function LaravelUi5\OData\Tests\Vocabularies\generatedVocabularyClasses;

/*
 * Every generated vocabulary class, found on disk (OP07). A regeneration after an upstream update is meant to be a
 * minor that nobody reads line by line; these checks catch a class the generator got wrong — one that does not load,
 * a field without its property, a TypeError instead of a loud LogicException, CSDL the schema refuses.
 *
 * Each class is built without arguments, so this covers empty records, markers and failing loud. Values are the
 * family tests' part (AnnotationStructureTest). No counts are asserted: they change with the upstream vocabularies.
 */

dataset('record classes', fn() => generatedVocabularyClasses(
    static fn(ReflectionClass $c) => $c->isSubclassOf(AbstractRecord::class),
));

dataset('terms', fn() => generatedVocabularyClasses(
    static fn(ReflectionClass $c) => $c->implementsInterface(TypedAnnotationInterface::class),
));

it('declares every field of a record class as a property it can write', function (string $class) {
    $reflection = new ReflectionClass($class);

    // TYPE names the class's own vocabulary: its namespace is the catalog entry of the class's PHP namespace.
    $vocabularies = [];
    foreach (VocabularyCatalog::default()->getEntries() as $entry) {
        $vocabularies[$entry->getPhpNamespace()] = $entry->getNamespace();
    }
    expect($class::TYPE)->toBe($vocabularies[$reflection->getNamespaceName()] . '.' . $reflection->getShortName());
    foreach ($class::FIELDS as $field => [$property, $type, $required]) {
        expect($reflection->hasProperty($property))->toBeTrue("{$class}: field {$field} has no property \${$property}")
            ->and($type)->not->toBe('')
            ->and($required)->toBeBool();
    }

    if ($reflection->isAbstract()) {
        return;
    }
    // Without arguments: a record, or a loud refusal naming a required field — nothing else.
    try {
        expect($reflection->newInstance()->toRecord())->toBeInstanceOf(RecordAnnotationValueInterface::class)
            ->and(array_filter($class::FIELDS, static fn(array $f) => $f[2]))->toBe([]);
    } catch (LogicException $e) {
        expect($e->getMessage())->toContain('requires the field');
    }
})->with('record classes');

it('writes a term without arguments, writes nothing, or fails loud — never another error', function (string $class) {
    try {
        $annotation = (new ReflectionClass($class))->newInstance()->toAnnotation();
    } catch (LogicException $e) {
        expect($e->getMessage())->toMatch('/requires (a value|the field)/');
        return;
    }

    expect($annotation->getTerm())->toBe($class::TERM)
        ->and(csdlSchemaErrors(annotatedCsdl([$annotation], [$annotation])))->toBe([]);
})->with('terms');
