<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Vocabularies;

/**
 * Build-time catalog of vocabulary sources for the generator.
 *
 * This interface is consumed exclusively by the Artisan generator command.
 * It defines the fixed, ordered set of vocabularies to process, including
 * their remote URIs and their intended PHP namespace prefixes.
 *
 * The catalog imposes a resolution order. Every vocabulary must appear
 * after all vocabularies it depends on, because the parser resolves
 * cross-vocabulary type references as it processes each entry. The
 * canonical order is:
 *
 *   1. Org.OData.Core.V1           (Core — no dependencies)
 *   2. Org.OData.Validation.V1     (depends on Core)
 *   3. Org.OData.Measures.V1       (depends on Core)
 *   4. Org.OData.Aggregation.V1    (depends on Core)
 *   5. Org.OData.Authorization.V1  (depends on Core)
 *   6. Org.OData.Capabilities.V1   (depends on Core, Authorization)
 *   7. com.sap.vocabularies.Common.v1  (depends on Core, Validation, Measures)
 *   8. com.sap.vocabularies.UI.v1      (depends on Core, Common)
 *   9. com.sap.vocabularies.Analytics.v1 (depends on Core, Common, Aggregation)
 *  10. com.sap.vocabularies.Communication.v1 (depends on Core, Common)
 *  11. com.sap.vocabularies.PersonalData.v1   (depends on Core, Common)
 *
 * This list is the recommended default. Implementations may restrict it
 * to a subset relevant to the project.
 *
 * This interface must not be used in production application code.
 * It belongs in the generator tooling only.
 */
interface VocabularyCatalogInterface
{
    /**
     * Returns the ordered list of vocabulary entries to generate.
     *
     * Entries must be ordered so that each vocabulary appears after all
     * vocabularies it depends on. The generator processes entries in
     * list order and will fail if a dependency is referenced before it
     * has been processed.
     *
     * @return list<VocabularyEntryInterface>
     */
    public function getEntries(): array;

    /**
     * Returns the entry for the given OData namespace, or null when
     * this catalog does not include that vocabulary.
     */
    public function getEntry(string $namespace): ?VocabularyEntryInterface;
}