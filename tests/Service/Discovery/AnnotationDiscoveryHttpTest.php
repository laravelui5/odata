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
uses(TestCase::class)
    ->beforeEach(function () {
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

    it('includes LineItem annotation on entity type', function () {
        $response = $this->get('/odata/$metadata');
        $xml = $response->streamedContent();

        expect($xml)->toContain('Term="com.sap.vocabularies.UI.v1.LineItem"');
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
