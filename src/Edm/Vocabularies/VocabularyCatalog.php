<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Vocabularies;

/**
 * Build-time ordered catalog of all vocabulary sources for the generator.
 *
 * Entries are listed in dependency order: every vocabulary appears after
 * all vocabularies it depends on. The generator processes entries in this
 * exact order so that cross-vocabulary type references resolve correctly.
 *
 * @see VocabularyCatalogInterface
 */
final readonly class VocabularyCatalog implements VocabularyCatalogInterface
{
    /** @param list<VocabularyEntryInterface> $entries */
    public function __construct(
        private array $entries,
    ) {}

    /**
     * Returns the canonical default catalog containing all supported
     * OData and SAP vocabularies in dependency order.
     */
    public static function default(): self
    {
        return new self([
            new VocabularyEntry(
                namespace:    'Org.OData.Core.V1',
                alias:        'Core',
                uri:          'https://oasis-tcs.github.io/odata-vocabularies/vocabularies/Org.OData.Core.V1.xml',
                phpNamespace: 'LaravelUi5\\OData\\Vocabularies\\Core\\V1',
                dependencies: [],
            ),
            new VocabularyEntry(
                namespace:    'Org.OData.Validation.V1',
                alias:        'Validation',
                uri:          'https://oasis-tcs.github.io/odata-vocabularies/vocabularies/Org.OData.Validation.V1.xml',
                phpNamespace: 'LaravelUi5\\OData\\Vocabularies\\Validation\\V1',
                dependencies: ['Core'],
            ),
            new VocabularyEntry(
                namespace:    'Org.OData.Measures.V1',
                alias:        'Measures',
                uri:          'https://oasis-tcs.github.io/odata-vocabularies/vocabularies/Org.OData.Measures.V1.xml',
                phpNamespace: 'LaravelUi5\\OData\\Vocabularies\\Measures\\V1',
                dependencies: ['Core'],
            ),
            new VocabularyEntry(
                namespace:    'Org.OData.Aggregation.V1',
                alias:        'Aggregation',
                uri:          'https://oasis-tcs.github.io/odata-vocabularies/vocabularies/Org.OData.Aggregation.V1.xml',
                phpNamespace: 'LaravelUi5\\OData\\Vocabularies\\Aggregation\\V1',
                dependencies: ['Core'],
            ),
            new VocabularyEntry(
                namespace:    'Org.OData.Authorization.V1',
                alias:        'Authorization',
                uri:          'https://oasis-tcs.github.io/odata-vocabularies/vocabularies/Org.OData.Authorization.V1.xml',
                phpNamespace: 'LaravelUi5\\OData\\Vocabularies\\Authorization\\V1',
                dependencies: ['Core'],
            ),
            new VocabularyEntry(
                namespace:    'Org.OData.Capabilities.V1',
                alias:        'Capabilities',
                uri:          'https://oasis-tcs.github.io/odata-vocabularies/vocabularies/Org.OData.Capabilities.V1.xml',
                phpNamespace: 'LaravelUi5\\OData\\Vocabularies\\Capabilities\\V1',
                dependencies: ['Core', 'Authorization'],
            ),
            new VocabularyEntry(
                namespace:    'com.sap.vocabularies.Common.v1',
                alias:        'Common',
                uri:          'https://sap.github.io/odata-vocabularies/vocabularies/Common.xml',
                phpNamespace: 'LaravelUi5\\OData\\Vocabularies\\Common\\V1',
                dependencies: ['Core', 'Validation', 'Measures'],
            ),
            new VocabularyEntry(
                namespace:    'com.sap.vocabularies.UI.v1',
                alias:        'UI',
                uri:          'https://sap.github.io/odata-vocabularies/vocabularies/UI.xml',
                phpNamespace: 'LaravelUi5\\OData\\Vocabularies\\Ui\\V1',
                dependencies: ['Core', 'Common'],
            ),
            new VocabularyEntry(
                namespace:    'com.sap.vocabularies.Analytics.v1',
                alias:        'Analytics',
                uri:          'https://sap.github.io/odata-vocabularies/vocabularies/Analytics.xml',
                phpNamespace: 'LaravelUi5\\OData\\Vocabularies\\Analytics\\V1',
                dependencies: ['Core', 'Common', 'Aggregation'],
            ),
            new VocabularyEntry(
                namespace:    'com.sap.vocabularies.Communication.v1',
                alias:        'Communication',
                uri:          'https://sap.github.io/odata-vocabularies/vocabularies/Communication.xml',
                phpNamespace: 'LaravelUi5\\OData\\Vocabularies\\Communication\\V1',
                dependencies: ['Core', 'Common'],
            ),
            new VocabularyEntry(
                namespace:    'com.sap.vocabularies.PersonalData.v1',
                alias:        'PersonalData',
                uri:          'https://sap.github.io/odata-vocabularies/vocabularies/PersonalData.xml',
                phpNamespace: 'LaravelUi5\\OData\\Vocabularies\\PersonalData\\V1',
                dependencies: ['Core', 'Common'],
            ),
        ]);
    }

    /** @return list<VocabularyEntryInterface> */
    public function getEntries(): array
    {
        return $this->entries;
    }

    public function getEntry(string $namespace): ?VocabularyEntryInterface
    {
        foreach ($this->entries as $entry) {
            if ($entry->getNamespace() === $namespace) {
                return $entry;
            }
        }
        return null;
    }
}
