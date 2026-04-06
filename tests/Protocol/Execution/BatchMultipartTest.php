<?php

declare(strict_types=1);

use LaravelUi5\OData\Fixtures\FlightServiceRegistry;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Tests\TestCase;

/**
 * Tier-4 HTTP tests for multipart/mixed batch requests.
 *
 * Adapted from flat3/lodata BatchMultipartTest for the read-only engine.
 * Uses FlightService + FlightServiceRegistry as the test fixture.
 */
uses(TestCase::class)
    ->beforeEach(function () {
        $this->withExceptionHandling();

        $this->app->instance(
            ODataServiceRegistryInterface::class,
            new FlightServiceRegistry(),
        );

        \LaravelUi5\OData\Fixtures\Models\Flight::insert([
            ['origin' => 'lhr', 'destination' => 'lax', 'gate' => 1, 'duration' => 41100.0],
            ['origin' => 'sfo', 'destination' => 'lax', 'gate' => 2, 'duration' => 2133.0],
            ['origin' => 'jfk', 'destination' => 'ord', 'gate' => 3, 'duration' => 3600.0],
        ]);

        \LaravelUi5\OData\Fixtures\Models\Passenger::insert([
            ['name' => 'Alice', 'flight_id' => 1],
            ['name' => 'Bob',   'flight_id' => 1],
            ['name' => 'Carol', 'flight_id' => 2],
        ]);
    });

// ── Helper ───────────────────────────────────────────────────────────────────

/**
 * Send a multipart/mixed batch POST request.
 *
 * @param  string  $body      The raw multipart body.
 * @param  string  $boundary  The boundary string (without the leading --).
 * @param  bool    $useCrlf   Whether to convert LF to CRLF (default true).
 */
function postMultipartBatch(
    mixed $test,
    string $body,
    string $boundary,
    bool $useCrlf = true,
): \Illuminate\Testing\TestResponse {
    if ($useCrlf) {
        // Normalize to CRLF as per HTTP/multipart spec.
        $body = str_replace("\r\n", "\n", $body);
        $body = str_replace("\n", "\r\n", $body);
    }

    return $test->call(
        'POST',
        '/odata/$batch',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'multipart/mixed; boundary=' . $boundary,
            'HTTP_ACCEPT'  => 'multipart/mixed',
        ],
        $body,
    );
}

// ── Missing / invalid boundary ───────────────────────────────────────────────

it('multipart batch with missing boundary returns 400', function () {
    $response = $this->call(
        'POST',
        '/odata/$batch',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'multipart/mixed'],
        '',
    );

    $response->assertStatus(400);
});

// ── Single entity by key (full URL) ─────────────────────────────────────────

it('multipart batch with full URL resolves entity', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77b';

    $response = postMultipartBatch($this, <<<MULTIPART
--{$boundary}
Content-Type: application/http

GET http://localhost/odata/Flights(1) HTTP/1.1
Host: localhost


--{$boundary}--
MULTIPART, $boundary);

    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))->toContain('multipart/mixed');

    $body = $response->streamedContent();
    expect($body)->toContain('HTTP/1.1 200 OK')
        ->and($body)->toContain('"origin":"lhr"');
});

// ── Query parameter ──────────────────────────────────────────────────────────

it('multipart batch with query parameters', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77c';

    $response = postMultipartBatch($this, <<<MULTIPART
--{$boundary}
Content-Type: application/http

GET http://localhost/odata/Flights?\$top=1 HTTP/1.1
Host: localhost


--{$boundary}--
MULTIPART, $boundary);

    $response->assertStatus(200);

    $body = $response->streamedContent();
    expect($body)->toContain('HTTP/1.1 200 OK')
        ->and($body)->toContain('"value"');
});

// ── Absolute path ────────────────────────────────────────────────────────────

it('multipart batch with absolute path', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77b';

    $response = postMultipartBatch($this, <<<MULTIPART
--{$boundary}
Content-Type: application/http

GET /odata/Flights(1) HTTP/1.1
Host: localhost


--{$boundary}--
MULTIPART, $boundary);

    $response->assertStatus(200);

    $body = $response->streamedContent();
    expect($body)->toContain('HTTP/1.1 200 OK')
        ->and($body)->toContain('"origin":"lhr"');
});

// ── Relative path ────────────────────────────────────────────────────────────

it('multipart batch with relative path', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77b';

    $response = postMultipartBatch($this, <<<MULTIPART
--{$boundary}
Content-Type: application/http

GET Flights(1) HTTP/1.1
Host: localhost


--{$boundary}--
MULTIPART, $boundary);

    $response->assertStatus(200);

    $body = $response->streamedContent();
    expect($body)->toContain('HTTP/1.1 200 OK')
        ->and($body)->toContain('"origin":"lhr"');
});

// ── Service document ─────────────────────────────────────────────────────────

it('multipart batch requesting service document', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77b';

    $response = postMultipartBatch($this, <<<MULTIPART
--{$boundary}
Content-Type: application/http

GET /odata/ HTTP/1.1
Host: localhost


--{$boundary}--
MULTIPART, $boundary);

    $response->assertStatus(200);

    $body = $response->streamedContent();
    expect($body)->toContain('HTTP/1.1 200 OK')
        ->and($body)->toContain('"Flights"');
});

// ── $metadata document ───────────────────────────────────────────────────────

it('multipart batch requesting metadata document', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77b';

    $response = postMultipartBatch($this, <<<'MULTIPART'
--batch_36522ad7-fc75-4b56-8c71-56071383e77b
Content-Type: application/http

GET /odata/$metadata HTTP/1.1
Host: localhost


--batch_36522ad7-fc75-4b56-8c71-56071383e77b--
MULTIPART, $boundary);

    $response->assertStatus(200);

    $body = $response->streamedContent();
    expect($body)->toContain('HTTP/1.1 200 OK')
        ->and($body)->toContain('edmx:Edmx');
});

// ── Not found ────────────────────────────────────────────────────────────────

it('multipart batch with unknown entity set returns inner 404', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77b';

    $response = postMultipartBatch($this, <<<MULTIPART
--{$boundary}
Content-Type: application/http

GET notfound HTTP/1.1
Host: localhost


--{$boundary}--
MULTIPART, $boundary);

    $response->assertStatus(200);

    $body = $response->streamedContent();
    expect($body)->toContain('HTTP/1.1 400 Bad Request');
});

// ── Bad request (invalid key) ────────────────────────────────────────────────

it('multipart batch with invalid key returns inner error', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77b';

    $response = postMultipartBatch($this, <<<MULTIPART
--{$boundary}
Content-Type: application/http

GET Flights('a') HTTP/1.1
Host: localhost


--{$boundary}--
MULTIPART, $boundary);

    $response->assertStatus(200);

    $body = $response->streamedContent();
    expect($body)->toContain('"error"');
});

// ── Non-GET method rejected ──────────────────────────────────────────────────

it('multipart batch rejects non-GET methods', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77b';

    $response = postMultipartBatch($this, <<<MULTIPART
--{$boundary}
Content-Type: application/http

POST /odata/Flights HTTP/1.1
Host: localhost
Content-Type: application/json

{"origin":"test"}

--{$boundary}--
MULTIPART, $boundary);

    $response->assertStatus(400);
});

// ── Prologue is ignored ──────────────────────────────────────────────────────

it('multipart batch ignores prologue before first boundary', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77b';

    $response = postMultipartBatch($this, <<<MULTIPART
This is a prologue and should be ignored.
--{$boundary}
Content-Type: application/http

GET /odata/Flights(1) HTTP/1.1
Host: localhost


--{$boundary}--
MULTIPART, $boundary);

    $response->assertStatus(200);

    $body = $response->streamedContent();
    expect($body)->toContain('HTTP/1.1 200 OK')
        ->and($body)->toContain('"origin":"lhr"');
});

// ── No epilogue ──────────────────────────────────────────────────────────────

it('multipart batch works without closing -- epilogue marker', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77b';

    $response = postMultipartBatch($this, <<<MULTIPART
--{$boundary}
Content-Type: application/http

GET /odata/Flights(1) HTTP/1.1
Host: localhost


--{$boundary}
MULTIPART, $boundary);

    $response->assertStatus(200);

    $body = $response->streamedContent();
    expect($body)->toContain('HTTP/1.1 200 OK')
        ->and($body)->toContain('"origin":"lhr"');
});

// ── Non-CRLF newlines ────────────────────────────────────────────────────────

it('multipart batch works with bare LF newlines', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77b';

    // Pass useCrlf=false so bare LF is preserved.
    $response = postMultipartBatch($this, <<<MULTIPART
--{$boundary}
Content-Type: application/http

GET Flights(1) HTTP/1.1
Host: localhost


--{$boundary}--
MULTIPART, $boundary, false);

    $response->assertStatus(200);

    $body = $response->streamedContent();
    expect($body)->toContain('HTTP/1.1 200 OK')
        ->and($body)->toContain('"origin":"lhr"');
});

// ── Multiple requests in one batch ───────────────────────────────────────────

it('multipart batch with multiple GET requests returns all responses', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77b';

    $response = postMultipartBatch($this, <<<MULTIPART
--{$boundary}
Content-Type: application/http

GET /odata/Flights(1) HTTP/1.1
Host: localhost


--{$boundary}
Content-Type: application/http

GET /odata/Flights(2) HTTP/1.1
Host: localhost


--{$boundary}
Content-Type: application/http

GET /odata/Passengers HTTP/1.1
Host: localhost


--{$boundary}--
MULTIPART, $boundary);

    $response->assertStatus(200);

    $body = $response->streamedContent();

    // All three inner responses should be 200 OK.
    preg_match_all('/HTTP\/1\.1 (\d+)/', $body, $matches);
    expect($matches[1])->toHaveCount(3)
        ->and($matches[1][0])->toBe('200')
        ->and($matches[1][1])->toBe('200')
        ->and($matches[1][2])->toBe('200');

    expect($body)->toContain('"origin":"lhr"')
        ->and($body)->toContain('"origin":"sfo"')
        ->and($body)->toContain('"Alice"');
});

// ── Inner request with $expand ───────────────────────────────────────────────

it('multipart batch with $expand in inner request', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77b';

    $response = postMultipartBatch($this, <<<MULTIPART
--{$boundary}
Content-Type: application/http

GET Flights(1)?\$expand=passengers HTTP/1.1
Host: localhost


--{$boundary}--
MULTIPART, $boundary);

    $response->assertStatus(200);

    $body = $response->streamedContent();
    expect($body)->toContain('HTTP/1.1 200 OK')
        ->and($body)->toContain('"passengers"')
        ->and($body)->toContain('"Alice"');
});

// ── Partial failure (one inner request fails, others succeed) ────────────────

it('multipart batch with partial failure does not abort batch', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77b';

    $response = postMultipartBatch($this, <<<MULTIPART
--{$boundary}
Content-Type: application/http

GET /odata/Flights(1) HTTP/1.1
Host: localhost


--{$boundary}
Content-Type: application/http

GET /odata/Flights(999) HTTP/1.1
Host: localhost


--{$boundary}
Content-Type: application/http

GET /odata/Flights(2) HTTP/1.1
Host: localhost


--{$boundary}--
MULTIPART, $boundary);

    $response->assertStatus(200);

    $body = $response->streamedContent();

    preg_match_all('/HTTP\/1\.1 (\d+)/', $body, $matches);
    expect($matches[1])->toHaveCount(3)
        ->and($matches[1][0])->toBe('200')
        ->and($matches[1][1])->toBe('404')
        ->and($matches[1][2])->toBe('200');
});

// ── Response format ──────────────────────────────────────────────────────────

it('multipart batch response has correct multipart/mixed structure', function () {
    $boundary = 'batch_36522ad7-fc75-4b56-8c71-56071383e77b';

    $response = postMultipartBatch($this, <<<MULTIPART
--{$boundary}
Content-Type: application/http

GET /odata/Flights(1) HTTP/1.1
Host: localhost


--{$boundary}--
MULTIPART, $boundary);

    $response->assertStatus(200);

    // Response Content-Type should be multipart/mixed with a boundary.
    $responseContentType = $response->headers->get('Content-Type');
    expect($responseContentType)->toContain('multipart/mixed')
        ->and($responseContentType)->toContain('boundary=');

    // Response should have OData-Version header.
    expect($response->headers->get('OData-Version'))->toBe('4.0');

    // Body should have proper multipart structure.
    $body = $response->streamedContent();
    expect($body)->toContain('Content-Type: application/http')
        ->and($body)->toContain('HTTP/1.1 200 OK');

    // Should end with closing boundary.
    expect($body)->toMatch('/--batchresponse_[a-f0-9]+--/');
});

// ── Real-world UI5 V4 batch format ───────────────────────────────────────────

it('handles UI5 V4 ODataModel batch format', function () {
    $boundary = 'batch_id-1773924359314-19';

    $response = postMultipartBatch($this, <<<MULTIPART
--{$boundary}
Content-Type:application/http
Content-Transfer-Encoding:binary

GET Flights?\$skip=0&\$top=100 HTTP/1.1
Accept:application/json;odata.metadata=minimal;IEEE754Compatible=true
Accept-Language:en-US
Content-Type:application/json;charset=UTF-8;IEEE754Compatible=true


--{$boundary}--
MULTIPART, $boundary);

    $response->assertStatus(200);

    $body = $response->streamedContent();
    expect($body)->toContain('HTTP/1.1 200 OK')
        ->and($body)->toContain('"value"');
});
