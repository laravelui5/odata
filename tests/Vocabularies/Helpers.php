<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Tests\Vocabularies;

use DOMDocument;
use DOMElement;
use DOMXPath;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Serialization\CsdlSerializer;
use LibXMLError;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/*
 * Helpers for the vocabulary tests: a one-type service to put annotations into $metadata, and the OASIS schema check.
 */

/**
 * The CSDL XML of a one-type service: `$typeAnnotations` on the entity type `Probe.Ns.Airport`, `$propertyAnnotations`
 * on its key `code`. The entity set is there because the schema refuses an empty entity container.
 *
 * @param list<TypedAnnotationInterface|AnnotationInterface> $typeAnnotations
 * @param list<TypedAnnotationInterface|AnnotationInterface> $propertyAnnotations
 */
function annotatedCsdl(array $typeAnnotations = [], array $propertyAnnotations = []): string
{
    $built = static fn(array $annotations): array => array_map(
        static fn(TypedAnnotationInterface|AnnotationInterface $a) => $a instanceof TypedAnnotationInterface ? $a->toAnnotation() : $a,
        $annotations,
    );
    $code = new Property('code', new PrimitiveType(EdmPrimitiveType::String), annotations: $built($propertyAnnotations));
    $type = new EntityType('Probe.Ns', 'Airport', key: [$code], declaredProperties: [$code], annotations: $built($typeAnnotations));

    return (new CsdlSerializer())->serialize(
        (new EdmBuilder())->namespace('Probe.Ns')->addEntityType($type)->addEntitySet(new EntitySet('Airports', $type))->build(),
    );
}

function csdlXpath(string $xml): DOMXPath
{
    $doc = new DOMDocument();
    $doc->loadXML($xml);
    $xpath = new DOMXPath($doc);
    $xpath->registerNamespace('edm', 'http://docs.oasis-open.org/odata/ns/edm');

    return $xpath;
}

/**
 * The violations of a CSDL XML document against the OASIS schema in tests/schemas/ (through csdl.xsd, which gives
 * edmx.xsd the location of edm.xsd).
 *
 * @return list<string>
 */
function csdlSchemaErrors(string $xml): array
{
    $doc = new DOMDocument();
    $doc->loadXML($xml);
    $previous = libxml_use_internal_errors(true);
    libxml_clear_errors();
    $doc->schemaValidate(dirname(__DIR__) . '/schemas/csdl.xsd');
    $errors = array_map(static fn(LibXMLError $e) => trim($e->message), libxml_get_errors());
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    return $errors;
}

/** @return array<string, string> property → "Kind=value", for the attribute-notation values of a record */
function recordAttributes(DOMXPath $xpath, DOMElement $record): array
{
    $fields = [];
    foreach ($xpath->query('edm:PropertyValue', $record) as $pv) {
        foreach ($pv->attributes as $attribute) {
            if ($attribute->name !== 'Property') {
                $fields[$pv->getAttribute('Property')] = "{$attribute->name}={$attribute->value}";
            }
        }
    }

    return $fields;
}

/**
 * Every class under src/Vocabularies/ for which `$accept` holds, as a Pest dataset keyed by its path
 * (`Ui\V1\LineItem`). Found on disk, so it grows with each regeneration.
 *
 * @param callable(ReflectionClass<object>): bool $accept
 * @return array<string, array{0: class-string}>
 */
function generatedVocabularyClasses(callable $accept): array
{
    $root    = dirname(__DIR__, 2) . '/src/Vocabularies';
    $classes = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
        if (!str_ends_with((string) $file, '.php')) {
            continue;
        }
        $relative = str_replace('/', '\\', substr((string) $file, strlen($root) + 1, -4));
        $class    = 'LaravelUi5\\OData\\Vocabularies\\' . $relative;
        if (class_exists($class) && $accept(new ReflectionClass($class))) {
            $classes[$relative] = [$class];
        }
    }
    ksort($classes);

    return $classes;
}
