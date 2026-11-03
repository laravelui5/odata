<?php

declare(strict_types=1);

use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Tests\TestCase;

/*
 * $metadata from the document odata:cache wrote (OP12): served as is while it exists — the cache hit — before the
 * schema is loaded, with an ETag a client can revalidate.
 */

uses(TestCase::class);

/** A service whose schema must not be built: configure() throws. Its document lives at $file. */
function serviceWithCachedDocument(string $file): ODataServiceInterface
{
    return new class ($file) extends ODataService {
        public function __construct(private string $file)
        {
            parent::__construct();
        }

        public function cachedMetadataXMLPath(): string
        {
            return $this->file;
        }

        protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
        {
            throw new RuntimeException('The schema was loaded.');
        }
    };
}

function bindSingleService(ODataServiceInterface $service): void
{
    app()->instance(ODataServiceRegistryInterface::class, new class ($service) implements ODataServiceRegistryInterface {
        public function __construct(private ODataServiceInterface $service) {}
        public function services(): array { return [$this->service]; }
        public function resolve(string $fullPath): ODataServiceInterface { return $this->service; }
    });
}

beforeEach(function () {
    $this->withExceptionHandling();
    $this->document = tempnam(sys_get_temp_dir(), 'edmx');
    file_put_contents($this->document, '<?xml version="1.0"?><edmx:Edmx Version="4.0">cached</edmx:Edmx>');
    bindSingleService(serviceWithCachedDocument($this->document));
});

afterEach(fn () => @unlink($this->document));

it('serves $metadata from the cached document without loading the schema', function () {
    $response = $this->get('/odata/$metadata');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/xml;charset=utf-8')
        ->assertHeader('OData-Version')
        ->assertHeader('ETag');
    expect($response->streamedContent())->toBe(file_get_contents($this->document));
});

it('answers 304 when the client already holds the document', function () {
    $etag = $this->get('/odata/$metadata')->headers->get('ETag');

    $response = $this->get('/odata/$metadata', ['If-None-Match' => $etag]);

    $response->assertStatus(304)->assertHeader('ETag', $etag);
    expect($response->streamedContent())->toBe('');
});

it('takes the cold path when the document is not there', function () {
    unlink($this->document);

    // The cold path builds the schema, which this service refuses: the file is the only way it answers.
    expect($this->get('/odata/$metadata')->status())->toBe(500);
});
