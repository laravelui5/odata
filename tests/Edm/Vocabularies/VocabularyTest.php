<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Vocabularies\Vocabulary;
use LaravelUi5\OData\Edm\Vocabularies\VocabularyCatalog;
use LaravelUi5\OData\Edm\Vocabularies\VocabularyEntryInterface;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Serialization\CsdlSerializer;

/*
 * The Vocabulary enum and the catalog name the same vocabularies (OP44): a vocabulary whose classes ship can be
 * referenced with useVocabulary(), and an enum case never points at nothing.
 */

it('has a case for every vocabulary in the catalog', function () {
    $namespaces = array_map(
        static fn(VocabularyEntryInterface $entry) => $entry->getNamespace(),
        VocabularyCatalog::default()->getEntries(),
    );

    expect(array_map(static fn(Vocabulary $v) => $v->value, Vocabulary::cases()))->toEqualCanonicalizing($namespaces);
});

it('references every vocabulary through useVocabulary()', function (Vocabulary $vocabulary) {
    $entry = VocabularyCatalog::default()->getEntry($vocabulary->value);
    $xml   = (new CsdlSerializer())->serialize((new EdmBuilder())->namespace('Test.Ns')->useVocabulary($vocabulary)->build());

    expect($entry)->not->toBeNull()
        ->and($xml)->toContain('<edmx:Reference Uri="' . $entry?->getUri() . '">')
        ->and($xml)->toContain('Namespace="' . $vocabulary->value . '" Alias="' . $entry?->getAlias() . '"');
})->with(Vocabulary::cases());
