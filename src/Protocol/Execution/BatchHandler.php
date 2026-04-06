<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

use LaravelUi5\OData\Http\ODataResponse;
use LaravelUi5\OData\Exception\BadRequestException;
use LaravelUi5\OData\Exception\ProtocolException;
use LaravelUi5\OData\Http\ODataRequest;
use LaravelUi5\OData\Protocol\Planning\QueryPlanner;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles OData batch requests ($batch) in both JSON and multipart/mixed format.
 *
 * Parses the request body, dispatches each inner request through
 * the QueryPlanner + Engine pipeline, and streams the batch response.
 *
 * Only GET requests are supported (read-only engine). Inner requests
 * that fail produce an error response entry rather than aborting the
 * entire batch.
 *
 * @link https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_BatchRequest
 * @link https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#sec_MultipartBatchFormat
 */
final readonly class BatchHandler
{
    public function __construct(
        private RuntimeSchemaInterface $schema,
        private ODataServiceInterface  $service,
    ) {}

    /**
     * Handle a batch request. Detects JSON vs multipart/mixed from Content-Type.
     */
    public function handle(string $requestBody, ?string $contentType = null): ODataResponse
    {
        if ($contentType !== null && str_starts_with($contentType, 'multipart/mixed')) {
            return $this->handleMultipart($requestBody, $contentType);
        }

        return $this->handleJson($requestBody);
    }

    // ── JSON batch ───────────────────────────────────────────────────────────

    private function handleJson(string $requestBody): ODataResponse
    {
        $body = json_decode($requestBody, true);

        if (!is_array($body) || !array_key_exists('requests', $body) || !is_array($body['requests'])) {
            throw new BadRequestException(
                'missing_requests',
                'The provided JSON document did not contain a valid requests property'
            );
        }

        $requests = $this->validateRequests($body['requests']);

        $response = new ODataResponse(null, 200, [
            'Content-Type' => 'application/json;odata.metadata=minimal;charset=utf-8',
            'OData-Version' => '4.0',
        ]);

        $schema  = $this->schema;
        $service = $this->service;

        $response->setCallback(static function () use ($requests, $schema, $service): void {
            echo '{"responses":[';

            $first = true;
            foreach ($requests as $requestData) {
                if (!$first) {
                    echo ',';
                }

                $innerResponse = self::dispatchInnerRequest($requestData, $schema, $service);
                echo json_encode($innerResponse, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                $first = false;
            }

            echo ']}';
        });

        return $response;
    }

    // ── Multipart/mixed batch ────────────────────────────────────────────────

    private function handleMultipart(string $requestBody, string $contentType): ODataResponse
    {
        $boundary = $this->extractBoundary($contentType);
        if ($boundary === null) {
            throw new BadRequestException(
                'missing_boundary',
                'The multipart/mixed Content-Type header must include a boundary parameter'
            );
        }

        $requests = $this->parseMultipartParts($requestBody, $boundary);
        $this->validateMethodsAreGet($requests);

        $responseBoundary = 'batchresponse_' . bin2hex(random_bytes(16));
        $schema  = $this->schema;
        $service = $this->service;

        $response = new ODataResponse(null, 200, [
            'Content-Type' => 'multipart/mixed; boundary=' . $responseBoundary,
            'OData-Version' => '4.0',
        ]);

        $response->setCallback(static function () use ($requests, $schema, $service, $responseBoundary): void {
            foreach ($requests as $requestData) {
                $innerResult = self::dispatchInnerRequest($requestData, $schema, $service);

                $status     = $innerResult['status'];
                $statusText = self::httpStatusText($status);
                $body       = $innerResult['body'] ?? null;
                $bodyJson   = $body !== null
                    ? json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : '';

                echo "--{$responseBoundary}\r\n";
                echo "Content-Type: application/http\r\n";
                echo "\r\n";
                echo "HTTP/1.1 {$status} {$statusText}\r\n";

                if ($bodyJson !== '') {
                    echo "Content-Type: application/json;odata.metadata=minimal;charset=utf-8\r\n";
                    echo "OData-Version: 4.0\r\n";
                    echo "\r\n";
                    echo $bodyJson;
                } else {
                    echo "\r\n";
                }

                echo "\r\n";
            }

            echo "--{$responseBoundary}--\r\n";
        });

        return $response;
    }

    private function extractBoundary(string $contentType): ?string
    {
        if (preg_match('/boundary\s*=\s*"?([^";,\s]+)"?/i', $contentType, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Parse multipart body into an array of request descriptors.
     *
     * @return list<array{id: string, method: string, url: string}>
     */
    private function parseMultipartParts(string $body, string $boundary): array
    {
        // Normalize to LF for consistent parsing; the spec says CRLF but
        // real clients may send bare LF.
        $body = str_replace("\r\n", "\n", $body);

        $parts    = explode('--' . $boundary, $body);
        $requests = [];
        $id       = 0;

        // First element is prologue (before first boundary) — skip it.
        array_shift($parts);

        foreach ($parts as $part) {
            $trimmed = ltrim($part, "\n");

            // Closing boundary marker: "--" after the boundary.
            if ($trimmed === '--' || str_starts_with($trimmed, "--")) {
                break;
            }

            // Split part headers from HTTP message by double newline.
            $sections = explode("\n\n", $trimmed, 2);
            if (count($sections) < 2) {
                continue;
            }

            $httpMessage = trim($sections[1]);
            if ($httpMessage === '') {
                continue;
            }

            // Parse the HTTP request line: "GET /path HTTP/1.1" or "GET /path".
            $lines       = explode("\n", $httpMessage);
            $requestLine = trim($lines[0]);

            if (!preg_match('/^(GET|POST|PUT|PATCH|DELETE|HEAD)\s+(.+?)(?:\s+HTTP\/[\d.]+)?$/i', $requestLine, $m)) {
                continue;
            }

            $requests[] = [
                'id'     => (string) $id,
                'method' => strtoupper($m[1]),
                'url'    => trim($m[2]),
            ];

            $id++;
        }

        return $requests;
    }

    // ── Shared validation / dispatch ─────────────────────────────────────────

    /**
     * Validate JSON batch requests: check required keys and GET-only.
     *
     * @param list<array{id: string, method: string, url: string}> $requests
     * @return list<array{id: string, method: string, url: string}>
     */
    private function validateRequests(array $requests): array
    {
        foreach ($requests as $request) {
            if (!isset($request['id'], $request['method'], $request['url'])) {
                throw new BadRequestException(
                    'missing_request_properties',
                    'All requests must contain the "id", "method" and "url" properties'
                );
            }
        }

        $this->validateMethodsAreGet($requests);

        return $requests;
    }

    /**
     * Reject non-GET methods (read-only engine).
     */
    private function validateMethodsAreGet(array $requests): void
    {
        foreach ($requests as $request) {
            if (strtoupper($request['method']) !== 'GET') {
                throw new BadRequestException(
                    'unsupported_method',
                    sprintf(
                        'Request %s uses method "%s" — only GET is supported on this read-only service',
                        $request['id'],
                        $request['method']
                    )
                );
            }
        }
    }

    /**
     * @return array{id: string, status: int, headers?: array<string, string>, body?: mixed}
     */
    private static function dispatchInnerRequest(
        array $requestData,
        RuntimeSchemaInterface $schema,
        ODataServiceInterface $service,
    ): array {
        $url = $requestData['url'];

        // Strip full URL prefix (http://host/...) down to path.
        if (preg_match('#^https?://[^/]+(/.*)$#', $url, $m)) {
            $url = $m[1];
        }

        // Resolve the path relative to the service route.
        $route = $service->route();
        if (str_starts_with($url, $route . '/')) {
            $path = substr($url, strlen($route));
        } elseif (str_starts_with($url, '/')) {
            $path = substr($url, strlen('/' . ltrim($route, '/'))) ?: '/';
        } else {
            $path = '/' . $url;
        }

        // Parse query string if present.
        $queryString = null;
        if (($qPos = strpos($path, '?')) !== false) {
            $queryString = substr($path, $qPos + 1);
            $path        = substr($path, 0, $qPos);
        }

        $query = [];
        if ($queryString !== null) {
            parse_str($queryString, $query);
        }

        $planRequest = new ODataRequest(
            path:    $path,
            filter:  $query['$filter'] ?? null,
            select:  $query['$select'] ?? null,
            orderBy: $query['$orderby'] ?? null,
            top:     isset($query['$top']) ? (int) $query['$top'] : null,
            skip:    isset($query['$skip']) ? (int) $query['$skip'] : null,
            expand:  $query['$expand'] ?? null,
            search:  $query['$search'] ?? null,
            compute: $query['$compute'] ?? null,
            count:   ($query['$count'] ?? '') === 'true',
        );

        try {
            $plan     = (new QueryPlanner)->plan($planRequest, $schema);
            $response = (new Engine($schema, $service->endpoint()))->execute($plan);

            ob_start();
            $response->sendContent();
            $responseBody = ob_get_clean();

            $result = [
                'id'     => $requestData['id'],
                'status' => $response->getStatusCode(),
            ];

            $decoded = json_decode($responseBody, true);
            if ($decoded !== null) {
                $result['body'] = $decoded;
            } else {
                $result['body'] = $responseBody;
            }

            return $result;
        } catch (ProtocolException $e) {
            $errorResponse = $e->toResponse();
            return [
                'id'     => $requestData['id'],
                'status' => $errorResponse->getStatusCode(),
                'body'   => ['error' => $e->toError()],
            ];
        }
    }

    private static function httpStatusText(int $status): string
    {
        return Response::$statusTexts[$status] ?? 'Unknown';
    }
}
