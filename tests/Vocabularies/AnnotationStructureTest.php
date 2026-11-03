<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Vocabularies\CodeList\V1\CurrencyCodes;
use LaravelUi5\OData\Vocabularies\CodeList\V1\StandardCode;
use LaravelUi5\OData\Vocabularies\Common\V1\FieldControl;
use LaravelUi5\OData\Vocabularies\Common\V1\FieldControlType;
use LaravelUi5\OData\Vocabularies\Common\V1\Label;
use LaravelUi5\OData\Vocabularies\Common\V1\SortOrderType;
use LaravelUi5\OData\Vocabularies\Core\V1\Permission;
use LaravelUi5\OData\Vocabularies\Core\V1\Permissions;
use LaravelUi5\OData\Vocabularies\Ui\V1\CriticalityType;
use LaravelUi5\OData\Vocabularies\Ui\V1\DataField;
use LaravelUi5\OData\Vocabularies\Ui\V1\DataFieldForAction;
use LaravelUi5\OData\Vocabularies\Ui\V1\DataFieldWithUrl;
use LaravelUi5\OData\Vocabularies\Ui\V1\LineItem;
use LaravelUi5\OData\Vocabularies\Ui\V1\PresentationVariant;
use LaravelUi5\OData\Vocabularies\Validation\V1\Minimum;

use function LaravelUi5\OData\Tests\Vocabularies\annotatedCsdl;
use function LaravelUi5\OData\Tests\Vocabularies\csdlSchemaErrors;
use function LaravelUi5\OData\Tests\Vocabularies\csdlXpath;
use function LaravelUi5\OData\Tests\Vocabularies\recordAttributes;

/*
 * The generated vocabulary classes, one term family at a time, through the serializer into $metadata (OP07, step 8).
 * Each document is also validated against the OASIS CSDL schema in tests/schemas/.
 */

/** The annotation of `$term` on the entity type (or, with `$property`, on that property). */
function termAnnotation(DOMXPath $xpath, string $term, ?string $property = null): DOMElement
{
    $on = $property !== null ? "/edm:Property[@Name='{$property}']" : '';
    $nodes = $xpath->query("//edm:EntityType[@Name='Airport']{$on}/edm:Annotation[@Term='{$term}']");
    expect($nodes->length)->toBe(1);

    return $nodes->item(0);
}

describe('a collection of records', function () {
    it('writes each element as a record of its own concrete type, with only the fields that are set', function () {
        $xml = annotatedCsdl([new LineItem([
            new DataField(value: new Path('code'), label: 'Code', criticality: CriticalityType::Positive),
            new DataFieldForAction(action: 'Probe.Ns.approve', label: 'Approve'),
            new DataFieldWithUrl(value: new Path('name'), url: 'https://example.test'),
        ])]);
        $xpath   = csdlXpath($xml);
        $records = iterator_to_array($xpath->query(
            'edm:Collection/edm:Record',
            termAnnotation($xpath, 'com.sap.vocabularies.UI.v1.LineItem'),
        ));

        expect(array_map(static fn(DOMElement $r) => $r->getAttribute('Type'), $records))->toBe([
            'com.sap.vocabularies.UI.v1.DataField',
            'com.sap.vocabularies.UI.v1.DataFieldForAction',
            'com.sap.vocabularies.UI.v1.DataFieldWithUrl',
        ])
            ->and(recordAttributes($xpath, $records[0]))->toBe([
                'Label'       => 'String=Code',
                'Criticality' => 'EnumMember=com.sap.vocabularies.UI.v1.CriticalityType/Positive',
                'Value'       => 'Path=code',
            ])
            // Inline is non-nullable with a default: left out, not demanded.
            ->and(recordAttributes($xpath, $records[1]))->toBe(['Label' => 'String=Approve', 'Action' => 'String=Probe.Ns.approve'])
            // The inherited Value and the record's own Url, in field order.
            ->and(recordAttributes($xpath, $records[2]))->toBe(['Value' => 'Path=name', 'Url' => 'String=https://example.test'])
            ->and(csdlSchemaErrors($xml))->toBe([]);
    });

    it('fails loud on a string where the collection takes records', function () {
        new LineItem(['code'])->toAnnotation();
    })->throws(LogicException::class, 'A com.sap.vocabularies.UI.v1.DataFieldAbstract takes a record or an enumeration member, string given.');
});

describe('a term of one record', function () {
    it('writes Bool, a nested collection of records and a property path', function () {
        $xml = annotatedCsdl([new PresentationVariant(
            maxItems: 10,
            sortOrder: [new SortOrderType(property: 'code', descending: true)],
            includeGrandTotal: false,
        )]);
        $xpath  = csdlXpath($xml);
        $record = $xpath->query('edm:Record', termAnnotation($xpath, 'com.sap.vocabularies.UI.v1.PresentationVariant'))->item(0);
        $sort   = $xpath->query("edm:PropertyValue[@Property='SortOrder']/edm:Collection/edm:Record", $record);

        expect($record->getAttribute('Type'))->toBe('com.sap.vocabularies.UI.v1.PresentationVariantType')
            ->and(recordAttributes($xpath, $record))->toBe(['MaxItems' => 'Int=10', 'IncludeGrandTotal' => 'Bool=false'])
            ->and($sort->length)->toBe(1)
            ->and($sort->item(0)->getAttribute('Type'))->toBe('com.sap.vocabularies.Common.v1.SortOrderType')
            ->and(recordAttributes($xpath, $sort->item(0)))->toBe(['Property' => 'PropertyPath=code', 'Descending' => 'Bool=true'])
            ->and(csdlSchemaErrors($xml))->toBe([]);
    });

    it('fails loud when a required field is not set', function () {
        new CurrencyCodes(collectionPath: 'Currencies')->toAnnotation();
    })->throws(LogicException::class, 'The record com.sap.vocabularies.CodeList.v1.CodeListSource requires the field Url (named argument $url).');
});

describe('an enumeration', function () {
    it('writes a member, a path in its place, and several flags', function () {
        $xml = annotatedCsdl(propertyAnnotations: [
            new FieldControl(FieldControlType::Mandatory, qualifier: 'Static'),
            new FieldControl(new Path('fieldControl'), qualifier: 'Dynamic'),
            new Permissions([Permission::Read, Permission::Write]),
        ]);
        $xpath = csdlXpath($xml);
        $on    = "//edm:Property[@Name='code']/edm:Annotation";

        expect($xpath->query("{$on}[@Qualifier='Static']")->item(0)->getAttribute('EnumMember'))
            ->toBe('com.sap.vocabularies.Common.v1.FieldControlType/Mandatory')
            ->and($xpath->query("{$on}[@Qualifier='Dynamic']")->item(0)->getAttribute('Path'))->toBe('fieldControl')
            ->and(termAnnotation($xpath, 'Org.OData.Core.V1.Permissions', 'code')->getAttribute('EnumMember'))
            ->toBe('Org.OData.Core.V1.Permission/Read Org.OData.Core.V1.Permission/Write')
            ->and(csdlSchemaErrors($xml))->toBe([]);
    });
});

describe('a model path and a primitive type', function () {
    it('writes a property path from a string and a value path from a Path', function () {
        $xml = annotatedCsdl(propertyAnnotations: [
            new StandardCode('iso', qualifier: 'Model'),
            // The form OpenUI5 reads for a code list (…StandardCode/$Path), not the one CSDL names.
            new StandardCode(new Path('iso'), qualifier: 'Ui5'),
        ]);
        $xpath = csdlXpath($xml);
        $on    = "//edm:Property[@Name='code']/edm:Annotation[@Term='com.sap.vocabularies.CodeList.v1.StandardCode']";

        expect($xpath->query("{$on}[@Qualifier='Model']")->item(0)->getAttribute('PropertyPath'))->toBe('iso')
            ->and($xpath->query("{$on}[@Qualifier='Ui5']")->item(0)->getAttribute('Path'))->toBe('iso')
            ->and(csdlSchemaErrors($xml))->toBe([]);
    });

    it('takes the kind of an Edm.PrimitiveType value from the value, or from the type it is given', function () {
        $xml = annotatedCsdl(propertyAnnotations: [
            new Minimum(0, qualifier: 'Number'),
            new Minimum('2026-01-01', EdmPrimitiveType::Date, qualifier: 'Day'),
        ]);
        $xpath = csdlXpath($xml);
        $on    = "//edm:Property[@Name='code']/edm:Annotation[@Term='Org.OData.Validation.V1.Minimum']";

        expect($xpath->query("{$on}[@Qualifier='Number']")->item(0)->getAttribute('Int'))->toBe('0')
            ->and($xpath->query("{$on}[@Qualifier='Day']")->item(0)->getAttribute('Date'))->toBe('2026-01-01')
            ->and(csdlSchemaErrors($xml))->toBe([]);
    });

    it('fails loud on a term without the value it requires', function () {
        new Label()->toAnnotation();
    })->throws(LogicException::class, 'The term com.sap.vocabularies.Common.v1.Label requires a value.');
});

it('refuses a document the schema does not allow — the check itself works', function () {
    $xml = str_replace('Bool="false"', 'Boolean="1"', annotatedCsdl([new PresentationVariant(includeGrandTotal: false)]));

    expect(csdlSchemaErrors($xml))->toContain(
        "Element '{http://docs.oasis-open.org/odata/ns/edm}PropertyValue', attribute 'Boolean': The attribute 'Boolean' is not allowed.",
    );
});
