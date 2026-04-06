<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Vocabularies;

/**
 * Built-in vocabularies supported by the package.
 *
 * Use with EdmBuilderInterface::useVocabulary() to declare which
 * vocabulary references the $metadata document should include.
 */
enum Vocabulary: string
{
    case Core          = 'Org.OData.Core.V1';
    case Validation    = 'Org.OData.Validation.V1';
    case Measures      = 'Org.OData.Measures.V1';
    case Aggregation   = 'Org.OData.Aggregation.V1';
    case Authorization = 'Org.OData.Authorization.V1';
    case Capabilities  = 'Org.OData.Capabilities.V1';
    case Common        = 'com.sap.vocabularies.Common.v1';
    case UI            = 'com.sap.vocabularies.UI.v1';
    case Analytics     = 'com.sap.vocabularies.Analytics.v1';
    case Communication = 'com.sap.vocabularies.Communication.v1';
    case PersonalData  = 'com.sap.vocabularies.PersonalData.v1';
}
