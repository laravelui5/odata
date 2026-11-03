<?php

declare(strict_types=1);

use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Fixtures\AnnotationDiscoveryServiceRegistry;
use LaravelUi5\OData\Tests\TestCase;

/**
 * Tier-4 HTTP round-trip tests for annotation discovery.
 *
 * Verifies that vocabulary annotations on Eloquent models appear in the
 * $metadata CSDL XML response after being discovered by ModelDiscovery.
 */
uses(TestCase::class);

beforeEach(function () {
    $this->withExceptionHandling();

    $this->app->instance(
        ODataServiceRegistryInterface::class,
        new AnnotationDiscoveryServiceRegistry(),
    );
});

// ── Entity type annotations in $metadata ─────────────────────────────────────

describe('Annotation discovery $metadata', function () {
    it('includes Description annotation on entity type', function () {
        $response = $this->get('/odata/$metadata');
        $response->assertStatus(200);

        $xml = $response->streamedContent();

        // Verify the Description annotation appears on the EntityType
        expect($xml)->toContain('Term="Org.OData.Core.V1.Description"')
            ->and($xml)->toContain('An airport with IATA code');
    });

    it('includes SelectionFields annotation on entity type', function () {
        $response = $this->get('/odata/$metadata');
        $xml = $response->streamedContent();

        expect($xml)->toContain('Term="com.sap.vocabularies.UI.v1.SelectionFields"');
    });

    it('serializes LineItem as a collection of typed DataField records', function () {
        $doc = new DOMDocument();
        $doc->loadXML($this->get('/odata/$metadata')->streamedContent());
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('edm', 'http://docs.oasis-open.org/odata/ns/edm');

        $records = iterator_to_array($xpath->query(
            '//edm:EntityType[@Name="AnnotatedAirport"]'
            . '/edm:Annotation[@Term="com.sap.vocabularies.UI.v1.LineItem"]/edm:Collection/edm:Record',
        ));
        // Property → "Kind=value" for the attribute-notation values of a record.
        $fields = static function (DOMElement $record) use ($xpath): array {
            $fields = [];
            foreach ($xpath->query('edm:PropertyValue', $record) as $pv) {
                foreach ($pv->attributes as $attribute) {
                    if ($attribute->name !== 'Property') {
                        $fields[$pv->getAttribute('Property')] = "{$attribute->name}={$attribute->value}";
                    }
                }
            }
            return $fields;
        };

        expect($records)->toHaveCount(2)
            ->and($records[0]->getAttribute('Type'))->toBe('com.sap.vocabularies.UI.v1.DataField')
            ->and($fields($records[0]))->toBe(['Label' => 'String=Code', 'Value' => 'Path=code'])
            ->and($records[1]->getAttribute('Type'))->toBe('com.sap.vocabularies.UI.v1.DataField')
            ->and($fields($records[1]))->toBe([
                'Criticality' => 'EnumMember=com.sap.vocabularies.UI.v1.CriticalityType/Positive',
                'Value'       => 'Path=name',
            ]);
    });

    it('serves a $metadata document that validates against the OASIS CSDL schema', function () {
        $doc = new DOMDocument();
        $doc->loadXML($this->get('/odata/$metadata')->streamedContent());

        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $valid  = $doc->schemaValidate(dirname(__DIR__, 2) . '/schemas/csdl.xsd');
        $errors = array_map(static fn(LibXMLError $e) => trim($e->message), libxml_get_errors());
        libxml_clear_errors();

        expect($errors)->toBe([])->and($valid)->toBeTrue();
    });

    it('includes Label annotation on property', function () {
        $response = $this->get('/odata/$metadata');
        $xml = $response->streamedContent();

        expect($xml)->toContain('Term="com.sap.vocabularies.Common.v1.Label"')
            ->and($xml)->toContain('IATA Code');
    });

    it('includes Hidden annotation on property', function () {
        $response = $this->get('/odata/$metadata');
        $xml = $response->streamedContent();

        expect($xml)->toContain('Term="com.sap.vocabularies.UI.v1.Hidden"');
    });

    it('serializes SelectionFields as Collection with PropertyPath items', function () {
        $response = $this->get('/odata/$metadata');
        $xml = $response->streamedContent();

        // Parse to verify structure
        $doc = new SimpleXMLElement($xml);
        $doc->registerXPathNamespace('edmx', 'http://docs.oasis-open.org/odata/ns/edmx');
        $doc->registerXPathNamespace('edm', 'http://docs.oasis-open.org/odata/ns/edm');

        $selectionFields = $doc->xpath(
            '//edm:EntityType[@Name="AnnotatedAirport"]'
            . '/edm:Annotation[@Term="com.sap.vocabularies.UI.v1.SelectionFields"]'
            . '/edm:Collection/edm:PropertyPath',
        );

        expect($selectionFields)->toHaveCount(2)
            ->and((string) $selectionFields[0])->toBe('code')
            ->and((string) $selectionFields[1])->toBe('name');
    });

    it('serializes Label as String constant on property', function () {
        $response = $this->get('/odata/$metadata');
        $xml = $response->streamedContent();

        $doc = new SimpleXMLElement($xml);
        $doc->registerXPathNamespace('edmx', 'http://docs.oasis-open.org/odata/ns/edmx');
        $doc->registerXPathNamespace('edm', 'http://docs.oasis-open.org/odata/ns/edm');

        $labels = $doc->xpath(
            '//edm:EntityType[@Name="AnnotatedAirport"]'
            . '/edm:Property[@Name="code"]'
            . '/edm:Annotation[@Term="com.sap.vocabularies.Common.v1.Label"]',
        );

        expect($labels)->toHaveCount(1)
            ->and((string) $labels[0]['String'])->toBe('IATA Code');
    });

    it('serializes Hidden as marker annotation (no value) on property', function () {
        $response = $this->get('/odata/$metadata');
        $xml = $response->streamedContent();

        $doc = new SimpleXMLElement($xml);
        $doc->registerXPathNamespace('edmx', 'http://docs.oasis-open.org/odata/ns/edmx');
        $doc->registerXPathNamespace('edm', 'http://docs.oasis-open.org/odata/ns/edm');

        $hidden = $doc->xpath(
            '//edm:EntityType[@Name="AnnotatedAirport"]'
            . '/edm:Property[@Name="country_id"]'
            . '/edm:Annotation[@Term="com.sap.vocabularies.UI.v1.Hidden"]',
        );

        expect($hidden)->toHaveCount(1);
        // Marker annotation: no String/Int/Collection child
        expect($hidden[0]->children('http://docs.oasis-open.org/odata/ns/edm')->count())->toBe(0);
    });
});
